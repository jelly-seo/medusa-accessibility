import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent


def notes(tag):
    version = tag.lstrip("vV")
    text = (ROOT / "CHANGELOG.md").read_text()
    m = re.search(rf"^## {re.escape(version)}\b.*?\n(.*?)(?=^## |\Z)", text, flags=re.S | re.M)
    return m.group(1).strip() if m else f"Medusa Accessibility Tools {version}"


if __name__ == "__main__":
    print(notes(sys.argv[1]))
