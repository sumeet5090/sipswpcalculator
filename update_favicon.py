import os

def update_favicons():
    for filename in os.listdir('.'):
        if filename.endswith('.php'):
            with open(filename, 'r', encoding='utf-8') as f:
                content = f.read()

            new_content = content.replace('type="image/png" href="/assets/favicon.png"', 'type="image/svg+xml" href="/assets/favicon.svg"')
            new_content = new_content.replace('"url": "https://sipswpcalculator.com/assets/favicon.png"', '"url": "https://sipswpcalculator.com/assets/favicon.svg"')

            if new_content != content:
                with open(filename, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                print(f"Updated favicon links in {filename}")

if __name__ == '__main__':
    update_favicons()
