import datetime
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
PLUGIN = ROOT / "medusa-accessibility-tools" / "medusa-accessibility-tools.php"
README = ROOT / "medusa-accessibility-tools" / "readme.txt"
LOG = ROOT / "CHANGELOG.md"


def sub(path, pattern, version):
    text = path.read_text()
    new, n = re.subn(pattern, lambda m: m.group(1) + version, text, count=1)
    if n != 1:
        raise SystemExit(f"Version not found in {path}")
    path.write_text(new)


def readme_changelog():
    entries = re.findall(r"^## (\S+) - (\S+)\n\n(.*?)(?=^## |\Z)", LOG.read_text(), flags=re.S | re.M)
    out = []
    for version, date, body in entries:
        out.append(f"= {version} - {date} =\n\n{body.strip()}\n")
    text = README.read_text()
    head, _, _ = text.partition("== Changelog ==")
    README.write_text(head + "== Changelog ==\n\n" + "\n".join(out))


def bump(version, notes):
    if not re.fullmatch(r"\d+\.\d+\.\d+", version):
        raise SystemExit("Use a version like 1.1.0")
    sub(PLUGIN, r"(Version:\s*)[0-9.]+", version)
    sub(PLUGIN, r"(define\( 'MEDUSA_A11Y_VERSION', ')[0-9.]+", version)
    sub(README, r"(Stable tag:\s*)[0-9.]+", version)
    entry = f"## {version} - {datetime.date.today().isoformat()}\n\n" + "\n".join(f"- {n}" for n in notes) + "\n\n"
    text = LOG.read_text() if LOG.exists() else "# Changelog\n\n"
    head, _, rest = text.partition("\n\n")
    LOG.write_text(head + "\n\n" + entry + rest)
    readme_changelog()
    print(f"{version} set")


if __name__ == "__main__":
    if len(sys.argv) < 3:
        raise SystemExit('Usage: python3 tools/release.py 1.1.0 "First change" "Second change"')
    bump(sys.argv[1], sys.argv[2:])
