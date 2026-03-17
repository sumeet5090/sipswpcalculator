import os
import re

files_to_fix = [
    "dollar-cost-averaging-tool.php",
    "retirement-drawdown-planner.php",
    "recurring-investment-calculator.php",
    "index.php"
]

for filename in files_to_fix:
    if not os.path.exists(filename):
        print(f"Skipping {filename} (not found)")
        continue
        
    with open(filename, "r") as f:
        content = f.read()
    
    original = content
    
    # 1. Fix the section tag for scrolling
    # Pattern to find the section wrapping the calculator
    pattern_section = r'<section\b([^>]*)\baria-labelledby="calculator-heading">'
    def section_replacer(match):
        attrs = match.group(1)
        # Ensure ID is present
        if 'id="calculator-section"' not in attrs:
            attrs += ' id="calculator-section"'
        # Ensure scroll-mt class is present (for future-proofing)
        if 'scroll-mt-32' not in attrs:
            attrs += ' class="scroll-mt-32"'
        # Inject the inline style
        if 'scroll-margin-top' not in attrs:
            attrs += ' style="scroll-margin-top: 120px;"'
        else:
            # Update existing style if possible, or just keep it
            pass
        return f'<section{attrs} aria-labelledby="calculator-heading">'

    content = re.sub(pattern_section, section_replacer, content)

    # 2. Fix the sticky top offset for the form column
    # Pattern: class="... lg:sticky ... top-X ..."
    pattern_sticky = r'(class="[^"]*lg:sticky\b[^"]*)top-\d+([^"]*")'
    content = re.sub(pattern_sticky, r'\1\2 style="top: 120px;"', content)
    
    # Clean up any potential double class tags if my manual replaces before were messy
    # pattern_sticky_alt = r'class="([^"]*lg:sticky[^"]*)"'
    # def sticky_replacer(match):
    #     cls = match.group(1)
    #     # Remove old top-N
    #     cls = re.sub(r'\btop-\d+\b', '', cls)
    #     return f'class="{cls}" style="top: 120px;"'
    
    # Let's just do a more targeted search for the specific div
    target_div_start = 'lg:col-span-1 flex flex-col gap-4 lg:sticky'
    if target_div_start in content:
        # Standardize the line
        content = content.replace(
            'lg:col-span-1 flex flex-col gap-4 lg:sticky lg:max-h-[calc(100vh-2rem)] lg:overflow-y-auto pb-4 lg:pr-4 custom-scrollbar',
            'lg:col-span-1 flex flex-col gap-4 lg:sticky lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto pb-4 lg:pr-4 custom-scrollbar'
        )
        content = content.replace(
            'lg:col-span-1 flex flex-col gap-4 lg:sticky lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto pb-4 lg:pr-4 custom-scrollbar',
            'lg:col-span-1 flex flex-col gap-4 lg:sticky lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto pb-4 lg:pr-4 custom-scrollbar" style="top: 120px;'
        )
        # Fix potential quote issues from previous step
        content = content.replace('scrollbar" style="top: 120px;"', 'scrollbar" style="top: 120px;"')

    if content != original:
        with open(filename, "w") as f:
            f.write(content)
        print(f"Successfully updated {filename}")
    else:
        print(f"No changes made to {filename} (patterns didn't match or already fixed)")
