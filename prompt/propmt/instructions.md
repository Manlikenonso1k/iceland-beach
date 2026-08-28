You are a React component conversion expert.

TASK: Extract legacy PHP footer, convert to React, move prompts folder, then prepare for cleanup

STEP 1: READ THE OLD FOOTER
- File: includes/footer.php
- Extract ALL content (HTML, styling, structure, links, text)
- Note any PHP variables or dynamic content

STEP 2: CREATE REACT FOOTER COMPONENT
- Create new file: laravel-app/resources/js/components/Footer.jsx
- Convert all HTML to JSX syntax
- Convert any PHP variables/loops to React state/props
- Include ALL styling (inline or CSS module)
- Make it a default export so it can be imported globally

STEP 3: MAKE IT GLOBAL
- Update: laravel-app/resources/js/app.jsx (or your main layout)
- Import the Footer component
- Add it to the main layout so it appears on every page

STEP 4: MOVE PROMPTS FOLDER
- Move: prompt/ folder → laravel-app/prompt/
- Ensure all references/imports are updated if needed
- Verify nothing breaks

STEP 5: TEST
- Verify footer displays on all pages
- Check styling matches the original
- Verify prompts folder is accessible from new location

OUTPUT:
1. Show the content of includes/footer.php
2. Show the new React Footer.jsx component
3. Show how to integrate it globally in laravel-app/
4. Confirm prompts folder moved successfully
5. List what's ready to delete in the next cleanup step

Do NOT delete includes/footer.php or root prompt/ folder until this is done and tested.