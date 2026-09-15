#!/usr/bin/env python3

from __future__ import annotations

import argparse
import json
import math
from pathlib import Path
import xml.etree.ElementTree as ET


def ratio(covered: int, total: int) -> float:
    if not total:
        return 100.0

    return math.floor((covered / total * 100) * 100) / 100


def badge_color(percent: float) -> str:
    if percent >= 80:
        return "#4c1"
    if percent >= 60:
        return "#dfb317"
    return "#e05d44"


def class_metrics(root: ET.Element, expected_total: int) -> tuple[int, int]:
    """Mirror PHPUnit class coverage: a class is covered when all methods are covered."""
    covered = 0
    total = 0

    for class_node in root.findall(".//class"):
        metrics = class_node.find("metrics")
        if metrics is None:
            continue

        methods = int(metrics.attrib.get("methods", "0"))
        if methods == 0:
            continue

        total += 1
        if int(metrics.attrib.get("coveredmethods", "0")) == methods:
            covered += 1

    if total != expected_total:
        raise RuntimeError(
            f"Clover class count mismatch: project reports {expected_total}, parsed {total}"
        )

    return covered, total


def parse_metrics(path: Path) -> dict[str, dict[str, int | float]]:
    root = ET.parse(path).getroot()
    metrics = root.find("./project/metrics")
    if metrics is None:
        raise RuntimeError("Clover report does not contain project metrics")

    total_classes = int(metrics.attrib.get("classes", "0"))
    covered_classes, parsed_classes = class_metrics(root, total_classes)

    values = {
        "lines": (
            int(metrics.attrib.get("coveredstatements", "0")),
            int(metrics.attrib.get("statements", "0")),
        ),
        "methods": (
            int(metrics.attrib.get("coveredmethods", "0")),
            int(metrics.attrib.get("methods", "0")),
        ),
        "classes": (covered_classes, parsed_classes),
    }

    return {
        name: {
            "percent": ratio(covered, total),
            "covered": covered,
            "total": total,
        }
        for name, (covered, total) in values.items()
    }


def render_badge(lines: dict[str, int | float]) -> str:
    percent = float(lines["percent"])
    label = f"{percent:.2f}%"
    color = badge_color(percent)

    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="130" height="20" role="img" aria-label="coverage: {label}">
  <title>coverage: {label}</title>
  <linearGradient id="s" x2="0" y2="100%">
    <stop offset="0" stop-color="#bbb" stop-opacity=".1"/>
    <stop offset="1" stop-opacity=".1"/>
  </linearGradient>
  <clipPath id="r"><rect width="130" height="20" rx="3" fill="#fff"/></clipPath>
  <g clip-path="url(#r)">
    <rect width="75" height="20" fill="#555"/>
    <rect x="75" width="55" height="20" fill="{color}"/>
    <rect width="130" height="20" fill="url(#s)"/>
  </g>
  <g fill="#fff" text-anchor="middle" font-family="Verdana,Geneva,DejaVu Sans,sans-serif" font-size="11">
    <text x="37.5" y="15" fill="#010101" fill-opacity=".3">coverage</text>
    <text x="37.5" y="14">coverage</text>
    <text x="102.5" y="15" fill="#010101" fill-opacity=".3">{label}</text>
    <text x="102.5" y="14">{label}</text>
  </g>
</svg>
'''


def render_summary(metrics: dict[str, dict[str, int | float]]) -> str:
    lines = metrics["lines"]
    methods = metrics["methods"]
    classes = metrics["classes"]

    return f'''<svg xmlns="http://www.w3.org/2000/svg" width="620" height="132" role="img" aria-label="Code coverage summary">
  <rect x="0.5" y="0.5" width="619" height="131" rx="8" fill="#0d1117" stroke="#30363d"/>
  <text x="24" y="30" fill="#f0f6fc" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif" font-size="18" font-weight="600">Code coverage</text>
  <text x="24" y="56" fill="#8c959f" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif" font-size="12">Latest successful main-branch measurement · PCOV · src/</text>

  <text x="24" y="91" fill="#8c959f" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif" font-size="12">Lines</text>
  <text x="24" y="114" fill="#f0f6fc" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif" font-size="18" font-weight="600">{float(lines['percent']):.2f}%</text>
  <text x="95" y="114" fill="#8c959f" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif" font-size="12">{lines['covered']} / {lines['total']}</text>

  <text x="230" y="91" fill="#8c959f" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif" font-size="12">Methods</text>
  <text x="230" y="114" fill="#f0f6fc" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif" font-size="18" font-weight="600">{float(methods['percent']):.2f}%</text>
  <text x="301" y="114" fill="#8c959f" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif" font-size="12">{methods['covered']} / {methods['total']}</text>

  <text x="436" y="91" fill="#8c959f" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif" font-size="12">Classes</text>
  <text x="436" y="114" fill="#f0f6fc" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif" font-size="18" font-weight="600">{float(classes['percent']):.2f}%</text>
  <text x="507" y="114" fill="#8c959f" font-family="-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif" font-size="12">{classes['covered']} / {classes['total']}</text>
</svg>
'''


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("clover", type=Path)
    parser.add_argument("output", type=Path)
    args = parser.parse_args()

    metrics = parse_metrics(args.clover)
    args.output.mkdir(parents=True, exist_ok=True)

    (args.output / "coverage.svg").write_text(render_badge(metrics["lines"]), encoding="utf-8")
    (args.output / "coverage-summary.svg").write_text(render_summary(metrics), encoding="utf-8")
    (args.output / "coverage.json").write_text(
        json.dumps({**metrics, "scope": "src/", "driver": "PCOV"}, indent=2) + "\n",
        encoding="utf-8",
    )


if __name__ == "__main__":
    main()
