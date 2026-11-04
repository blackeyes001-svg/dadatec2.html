<?php
session_start();

require_once 'includes/db.php';

set_time_limit(0);
ignore_user_abort(true);

$baseFolder = __DIR__ . '/images';
$creditFile = $baseFolder . '/image-credits.txt';

if (!extension_loaded('curl')) {
    exit('PHP cURL is not enabled. Please tell me.');
}

function requestUrl($url) {
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 35,
        CURLOPT_USERAGENT => 'BayogoalEnterprises/1.0 (sports store)'
    ]);

    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($result === false || $status !== 200) {
        throw new Exception("Request failed: " . $error);
    }

    return $result;
}

function searchProductImage($name, $category, &$usedImages) {

    $search = $name . ' sports equipment product';

    $url = 'https://commons.wikimedia.org/w/api.php?' .
        http_build_query([
            'action' => 'query',
            'generator' => 'search',
            'gsrsearch' => $search . ' filetype:bitmap',
            'gsrnamespace' => 6,
            'gsrlimit' => 30,
            'prop' => 'imageinfo',
            'iiprop' => 'url|mime|extmetadata',
            'iiurlwidth' => 900,
            'format' => 'json'
        ]);

    $data = json_decode(requestUrl($url), true);

    if (empty($data['query']['pages'])) {
        return false;
    }

    $pages = array_values($data['query']['pages']);

    shuffle($pages);

    foreach ($pages as $page) {

        if (empty($page['imageinfo'][0])) {
            continue;
        }

        $info = $page['imageinfo'][0];

        $mime = $info['mime'] ?? '';

        if (!in_array($mime, [
            'image/jpeg',
            'image/png',
            'image/webp'
        ])) {
            continue;
        }

        $imageUrl = $info['thumburl'] ?? $info['url'] ?? '';

        if (!$imageUrl || in_array($imageUrl, $usedImages, true)) {
            continue;
        }

        $metadata = $info['extmetadata'] ?? [];

        $license = strip_tags(
            $metadata['LicenseShortName']['value'] ?? ''
        );

        $licenseUrl = strip_tags(
            $metadata['LicenseUrl']['value'] ?? ''
        );

        $artist = strip_tags(
            $metadata['Artist']['value'] ?? ''
        );

        $source = $info['descriptionurl'] ?? '';

        if (!$license || !$licenseUrl || !$source) {
            continue;
        }

        $usedImages[] = $imageUrl;

        return [
            'url' => $imageUrl,
            'mime' => $mime,
            'license' => $license,
            'licenseUrl' => $licenseUrl,
            'artist' => $artist,
            'source' => $source
        ];
    }

    return false;
}

function extensionFromMime($mime) {
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ][$mime] ?? false;
}

// Fetch all existing products.
$stmt = $pdo->query(
    "SELECT id, name, category, image
     FROM products
     ORDER BY id ASC"
);

$products = $stmt->fetchAll();

$usedImages = [];
$success = 0;
$skipped = 0;
$failed = 0;

echo '<h2>Bayogoal Enterprises - Image Setup</h2>';
echo '<p>Processing your product catalogue...</p>';

foreach ($products as $product) {

    $id = (int)$product['id'];
    $name = $product['name'];
    $category = $product['category'];

    // Preserve existing image assignments.
    if (!empty($product['image'])) {
        echo '<p>SKIPPED: ' .
            htmlspecialchars($name) .
            ' - Existing image preserved.</p>';

        $skipped++;
        continue;
    }

    try {

        $image = searchProductImage(
            $name,
            $category,
            $usedImages
        );

        if (!$image) {
            throw new Exception('No suitable licensed image found.');
        }

        $extension = extensionFromMime($image['mime']);

        if (!$extension) {
            throw new Exception('Unsupported image format.');
        }

        $folder = $baseFolder . '/' . $category;

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $filename = 'product-' . $id . '.' . $extension;

        $filepath = $folder . '/' . $filename;

        $imageData = requestUrl($image['url']);

        if (strlen($imageData) < 1000) {
            throw new Exception('Image download incomplete.');
        }

        file_put_contents($filepath, $imageData);

        $relativePath = $category . '/' . $filename;

        $update = $pdo->prepare(
            "UPDATE products SET image = ?
             WHERE id = ? AND (image IS NULL OR image = '')"
        );

        $update->execute([$relativePath, $id]);

        if ($update->rowCount() === 0) {
            unlink($filepath);
            throw new Exception('Database image field was not updated.');
        }

        $credit = "\nProduct: $name (ID: $id)\n";
        $credit .= "Artist: {$image['artist']}\n";
        $credit .= "Licence: {$image['license']}\n";
        $credit .= "Licence URL: {$image['licenseUrl']}\n";
        $credit .= "Source: {$image['source']}\n";
        $credit .= "-----------------------------\n";

        file_put_contents(
            $GLOBALS['creditFile'],
            $credit,
            FILE_APPEND
        );

        echo '<p style="color:green;">SUCCESS: ' .
            htmlspecialchars($name) . '</p>';

        $success++;

    } catch (Throwable $e) {

        echo '<p style="color:red;">FAILED: ' .
            htmlspecialchars($name) . ' - ' .
            htmlspecialchars($e->getMessage()) . '</p>';

        $failed++;
    }

    flush();
}

echo '<hr>';
echo '<h2>Image Setup Completed</h2>';
echo "<p>Successful: $success</p>";
echo "<p>Skipped: $skipped</p>";
echo "<p>Failed: $failed</p>";

echo '<p>Review all images before using them commercially.</p>';
?>