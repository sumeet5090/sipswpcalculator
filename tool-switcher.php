<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segmented Control</title>
    <style>
        /* Defaults and Body Style for display */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f9fafb;
            padding: 2rem;
        }

        /* Segmented Control Container */
        .tool-switcher {
            display: flex;
            flex-direction: row;
            background-color: #f3f4f6; /* Equivalent to bg-gray-100 */
            border-radius: 9999px;   /* Pill-shaped */
            padding: 4px;
            max-width: 700px;
            margin: 2rem auto;
            border: 1px solid #e5e7eb;
        }

        /* Hide the actual radio buttons */
        .tool-switcher input[type="radio"] {
            display: none;
        }

        /* Label Styling */
        .tool-switcher label {
            flex: 1; /* Distribute space equally */
            text-align: center;
            padding: 0.65rem 1rem;
            cursor: pointer;
            border-radius: 9999px;
            font-weight: 500;
            color: #4b5563; /* Muted gray text */
            transition: all 0.3s ease; /* Smooth transition for all properties */
            white-space: nowrap;
        }

        /* Active State - when the associated radio button is checked */
        .tool-switcher input[type="radio"]:checked + label {
            background-color: white;
            font-weight: 700; /* Bold text */
            color: #111827;   /* Darker text */
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06); /* Slight shadow */
        }

        /* Responsive Breakpoint for Mobile */
        @media (max-width: 640px) {
            .tool-switcher {
                flex-direction: column; /* Stack the items vertically */
                border-radius: 1rem;    /* A less pronounced curve for vertical stacking */
                max-width: 90%;
            }

            .tool-switcher label {
                padding: 0.75rem 1rem; /* Slightly more vertical padding on mobile */
            }
        }
    </style>
</head>
<body>

    <div class="tool-switcher">
        <!-- Radio button and label for the Core Calculator -->
        <input type="radio" id="tool-core" name="tool-switcher" checked>
        <label for="tool-core">SIP/SWP Core</label>

        <!-- Radio button and label for the Goal Reverser -->
        <input type="radio" id="tool-reverser" name="tool-switcher">
        <label for="tool-reverser">Goal Reverser</label>

        <!-- Radio button and label for the Risk Analyzer -->
        <input type="radio" id="tool-analyzer" name="tool-switcher">
        <label for="tool-analyzer">Risk Analyzer</label>
    </div>

</body>
</html>
