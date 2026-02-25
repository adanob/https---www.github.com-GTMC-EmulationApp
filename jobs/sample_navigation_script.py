"""
Example Navigation Script - Portal Report Download

The navigation script is the sole authority on what happens.
The engine does not decide actions. This script does.

Every navigation script must define:
    def navigate(context: dict) -> dict

The context dict contains:
    - helper             BrowserHelper instance (click, type, screenshot, etc.)
    - tokens             dict of user-defined key-value pairs
    - credentials        dict with plaintext 'username' and 'password'
    - target_url         the URL to navigate to
    - download_dir       local directory where files are saved
    - s3                 S3Storage instance
    - s3_output_bucket   destination bucket (if provided in payload)
    - s3_output_prefix   destination prefix (if provided in payload)
    - logger             JobLogger instance (info, success, warning, error)

Downloads are uploaded to S3 by the engine after this script finishes.
The script does not need to handle S3 uploads unless it has a custom need.
"""


def navigate(context: dict) -> dict:
    helper = context["helper"]
    tokens = context["tokens"]
    creds  = context["credentials"]
    logger = context["logger"]

    # -- Navigate to the login page ------
    helper.go(context["target_url"])
    helper.wait_for_page_load()
    logger.info("Page loaded", context["target_url"])

    # -- Login ---------------------------
    helper.type_text('//input[@id="username"]', creds.get("username", ""))
    helper.type_text('//input[@id="password"]', creds.get("password", ""))
    helper.click('//button[@type="submit"]')
    helper.wait_for_page_load()
    logger.success("Login successful")

    # -- Navigate to reports using tokens -
    helper.click('//a[contains(text(), "Reports")]')
    helper.wait_for_page_load()
    logger.info("Navigated to Reports")

    helper.select_option('//select[@id="report_type"]', tokens["report_type"])
    helper.type_text('//input[@id="date_from"]', tokens["date_from"])
    helper.type_text('//input[@id="date_to"]', tokens["date_to"])
    logger.info("Filters applied", f"{tokens['date_from']} to {tokens['date_to']}")

    # -- Download the report -------------
    helper.click('//button[@id="download"]')
    downloaded_file = helper.wait_for_download(timeout=60)
    logger.success("File downloaded", downloaded_file or "No file")

    # -- Screenshot ----------------------
    screenshot = helper.screenshot()
    logger.info("Screenshot captured", screenshot)

    # Downloads are uploaded to S3 by the engine after this function returns.
    return {"downloaded_file": downloaded_file, "screenshot": screenshot}