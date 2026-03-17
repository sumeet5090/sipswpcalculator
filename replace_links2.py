import os
import glob
import re

php_files = glob.glob('*.php')

for file in php_files:
    with open(file, "r") as f:
        content = f.read()
    
    original = content
    
    # We want to replace href="/#calculator-heading" with href="/#calculator-section"
    
    content = content.replace('href="/#calculator-heading"', 'href="/#calculator-section"')
    content = content.replace('href="https://sipswpcalculator.com/#calculator-heading"', 'href="https://sipswpcalculator.com/#calculator-section"')

    if content != original:
        with open(file, "w") as f:
            f.write(content)
        print(f"Updated {file}")
