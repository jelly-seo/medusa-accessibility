import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SLUG = "medusa-accessibility-tools"
SRC = ROOT / SLUG
DIST = ROOT / "dist"
SKIP = {".DS_Store", "Thumbs.db"}


def main():
    DIST.mkdir(exist_ok=True)
    dest = DIST / f"{SLUG}.zip"
    with zipfile.ZipFile(dest, "w", zipfile.ZIP_DEFLATED) as z:
        for path in sorted(SRC.rglob("*")):
            if path.is_file() and path.name not in SKIP:
                z.write(path, Path(SLUG) / path.relative_to(SRC))
    print(dest.relative_to(ROOT))


if __name__ == "__main__":
    main()
