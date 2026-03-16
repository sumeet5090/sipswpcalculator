import os
import shutil

# 1. Provide logo.svg
shutil.copyfile('assets/favicon.svg', 'assets/logo.svg')

# 2. Update JSON-LD references in all PHP files
for filename in os.listdir('.'):
    if filename.endswith('.php'):
        with open(filename, 'r', encoding='utf-8') as f:
            content = f.read()

        new_content = content.replace('"url": "https://sipswpcalculator.com/assets/logo.png"', '"url": "https://sipswpcalculator.com/assets/logo.svg"')

        if new_content != content:
            with open(filename, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated logo URL in {filename}")
