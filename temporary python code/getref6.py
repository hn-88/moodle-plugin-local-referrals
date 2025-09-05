#!/usr/bin/env python3
import csv
import re
import gzip
from datetime import datetime, timedelta
from zoneinfo import ZoneInfo
import os

# ------------------------------
# Config
# ------------------------------
LOG_FILES = [
    "accessswayam.log",
    "accessswayam.log.1"
] + [f"accessswayam.log.{i}.gz" for i in range(2, 15+1)]  # accessswayam.log.2.gz ... accessswayam.log.14.gz

ENROL_CSV = "enrol.csv"
OUTPUT_CSV = "referral_mapping.csv"
TIME_WINDOW = timedelta(hours=2)  # max time between referral and enrolment

CSV_DELIMITER = ","  # Moodle CSV delimiter

# Regex for Apache log line with referralid
LOG_PATTERN = re.compile(
    r'\[(?P<ts>[^\]]+)\]\s+"GET\s+/course/view\.php\?[^"]*?referralid=(?P<refid>[a-f0-9]+)[^"]*?code=(?P<coursecode>[A-Za-z0-9_]+)',
)
APACHE_TS_FORMAT = "%d/%b/%Y:%H:%M:%S %z"
MOODLE_TS_FORMAT = "%A, %d %B %Y, %I:%M %p"

# ------------------------------
# Helpers
# ------------------------------
#def normalize_courseid(cid: str) -> str:
#    """Normalize course IDs to match across systems."""
#    return cid.replace("_", "").upper().strip()

# ------------------------------
# Step 1: Parse multiple Apache logs
# ------------------------------
apache_hits = []

for log_file in LOG_FILES:
    if not os.path.exists(log_file):
        print(f"⚠️  File not found, skipping: {log_file}")
        continue

    print(f"Processing {log_file} ...")
    # Choose gzip.open for .gz files
    open_func = gzip.open if log_file.endswith(".gz") else open

    with open_func(log_file, "rt", encoding="utf-8", errors="ignore") as f:
        for line in f:
            m = LOG_PATTERN.search(line)
            if m:
                ts = datetime.strptime(m["ts"], APACHE_TS_FORMAT)
                courseid = m["coursecode"]
                refid = m["refid"]
                apache_hits.append({
                    "courseid": courseid,
                    "referralid": refid,
                    "timestamp_utc": ts
                })

print(f"✅ Total referral hits collected: {len(apache_hits)}")

# ------------------------------
# Step 2: Parse Moodle enrolment CSV
# ------------------------------
enrolments = []

with open(ENROL_CSV, newline="", encoding="utf-8") as csvfile:
    reader = csv.DictReader(csvfile, delimiter=CSV_DELIMITER)
    for row in reader:
        enrolled_time_str = row.get("Registered Date", "").strip()
        if not enrolled_time_str:
            continue  # skip blank time

        try:
            dt_ist = datetime.strptime(enrolled_time_str, MOODLE_TS_FORMAT).replace(
                tzinfo=ZoneInfo("Asia/Kolkata")
            )
        except ValueError:
            print(f"⚠️  Skipping invalid date: {enrolled_time_str}")
            continue

        dt_utc = dt_ist.astimezone(ZoneInfo("UTC"))

        courseid_str = row.get("Course ID in SWAYAM Plus", "").strip()
        if not courseid_str:
            continue  # skip if no course ID

        enrolments.append({
            "user": row["Learner Name"],
            "courseid": courseid_str,
            "timestamp_utc": dt_utc
        })

print(f"✅ Total enrolments processed: {len(enrolments)}")

# ------------------------------
# Step 3: Match enrolments to referrals
# ------------------------------
matches = []

for enrol in enrolments:
    courseid = enrol["courseid"]
    ts_enrol = enrol["timestamp_utc"]

    candidates = [
        hit for hit in apache_hits
        if hit["courseid"] == courseid
        and ts_enrol - TIME_WINDOW <= hit["timestamp_utc"] <= ts_enrol
    ]

    if candidates:
        best = max(candidates, key=lambda x: x["timestamp_utc"])
        matches.append({
            "user": enrol["user"],
            "courseid": courseid,
            "referralid": best["referralid"],
            "referral_time_utc": best["timestamp_utc"].strftime("%Y-%m-%d %H:%M:%S"),
            "enrol_time_utc": ts_enrol.strftime("%Y-%m-%d %H:%M:%S")
        })

print(f"✅ Total matches found: {len(matches)}")

# ------------------------------
# Step 4: Write output CSV
# ------------------------------
fieldnames = ["user", "courseid", "referralid", "referral_time_utc", "enrol_time_utc"]

with open(OUTPUT_CSV, "w", newline="", encoding="utf-8") as f:
    writer = csv.DictWriter(f, fieldnames=fieldnames)
    writer.writeheader()
    for row in matches:
        writer.writerow(row)

print(f"✅ Saved referral mappings to {OUTPUT_CSV}")
