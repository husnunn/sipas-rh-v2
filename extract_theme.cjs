const fs = require('fs');
const content = fs.readFileSync('resources/reference_design/dashboard.html', 'utf8');

const colorsMatch = content.match(/"colors": {([^}]+)}/);
const colors = JSON.parse(`{${colorsMatch[1]}}`);

let cssOutput = `\n/* Custom Material Variables */\n`;

for (const [key, value] of Object.entries(colors)) {
  cssOutput += `    --color-${key}: ${value};\n`;
}

cssOutput += `\n    /* Spacing */\n`;
cssOutput += `    --spacing-sidebar-width: 260px;\n`;
cssOutput += `    --spacing-gutter: 16px;\n`;
cssOutput += `    --spacing-stack-md: 16px;\n`;
cssOutput += `    --spacing-unit: 4px;\n`;
cssOutput += `    --spacing-stack-sm: 8px;\n`;
cssOutput += `    --spacing-container-padding: 24px;\n`;
cssOutput += `    --spacing-stack-lg: 24px;\n`;

console.log(cssOutput);
