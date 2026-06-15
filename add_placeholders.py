import re

with open('event.php', 'r', encoding='utf-8') as f:
    c = f.read()

# Find <div... bg-sky-50 ...> followed by <img or <span
# Insert <!-- REPLACE WITH EVENT IMAGE --> before the inner tag
c = re.sub(r'(<div[^>]*bg-sky-50[^>]*>)\s*(<img|<span)', r'\1\n                            <!-- REPLACE WITH EVENT IMAGE -->\n                            \2', c)

with open('event.php', 'w', encoding='utf-8') as f:
    f.write(c)
