"""Every `<<< @/...` snippet import must point at an existing file and region."""
import re
import sys
from pathlib import Path

docs = Path(__file__).resolve().parent.parent
errors = []
for page in sorted(docs.rglob("*.md")):
    if "node_modules" in page.parts or ".vitepress" in page.parts:
        continue
    text = page.read_text(encoding="utf-8")
    for target in re.findall(r"^<<< @/(\S+?)(?:\{[^}]*\})?(?:\s|$)", text, re.M):
        path, _, region = target.partition("#")
        file = docs / path
        if not file.is_file():
            errors.append(f"{page.relative_to(docs)}: snippet {path} does not exist")
        elif region and f"#region {region}" not in file.read_text(encoding="utf-8"):
            errors.append(f"{page.relative_to(docs)}: snippet {path} has no region {region}")

for error in errors:
    print(error, file=sys.stderr)
if errors:
    sys.exit(1)
print("ok snippet imports point at existing files and regions")
