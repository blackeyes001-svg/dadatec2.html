class Employee:
    def __init__(self, name, age):
        self.name = name
        self.age = age

    def display_info(self):
        print(f"Name: {self.name}, Age: {self.age}")
# Create an instance of the Employee class
employee1 = Employee("John Doe", 30)
# Display the employee's informationemployee1.display_info()
employee1.display_info()