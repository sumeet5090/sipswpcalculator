import os
import glob

files_to_fix = [
    "dollar-cost-averaging-tool.php",
    "retirement-drawdown-planner.php",
    "recurring-investment-calculator.php",
    "index.php" # already done, but making sure
]

for filename in files_to_fix:
    if not os.path.exists(filename):
        continue
        
    with open(filename, "r") as f:
        content = f.read()
    
    original = content
    
    # 1. Add scroll-margin-top to the section
    # Search for the section that aria-labels the calculator-heading
    content = content.replace(
        '<section aria-labelledby="calculator-heading">', 
        '<section id="calculator-section" class="scroll-mt-32" style="scroll-margin-top: 120px;" aria-labelledby="calculator-heading">'
    )
    # Also catch if it already had the ID/class from previous partial hits
    content = content.replace(
        '<section id="calculator-section" class="scroll-mt-24" aria-labelledby="calculator-heading">',
        '<section id="calculator-section" class="scroll-mt-32" style="scroll-margin-top: 120px;" aria-labelledby="calculator-heading">'
    )
    content = content.replace(
        '<section id="calculator-section" class="scroll-mt-32" aria-labelledby="calculator-heading">',
        '<section id="calculator-section" class="scroll-mt-32" style="scroll-margin-top: 120px;" aria-labelledby="calculator-heading">'
    )

    # 2. Fix the sticky top offset
    content = content.replace(
        'class="lg:col-span-1 flex flex-col gap-4 lg:sticky lg:top-4 lg:max-h-[calc(100vh-2rem)]',
        'class="lg:col-span-1 flex flex-col gap-4 lg:sticky lg:max-h-[calc(100vh-8rem)]'
    )
    # Then inject the inline style top: 120px if not present
    if 'style="top: 120px;"' not in content and 'lg:col-span-1 flex flex-col gap-4 lg:sticky' in content:
        content = content.replace(
            'lg:col-span-1 flex flex-col gap-4 lg:sticky',
            'lg:col-span-1 flex flex-col gap-4 lg:sticky style="top: 120px;"'
        )
        # Fix the misplaced quote if it happened (regex would be better but simple replace is safer for me to visualize)
        content = content.replace('sticky style="top: 120px;"', 'sticky" style="top: 120px;"')

    if content != original:
        with open(filename, "w") as f:
            f.write(content)
        print(f"Fixed {filename}")
    else:
        print(f"Checking {filename} - no changes needed or pattern not matched")
