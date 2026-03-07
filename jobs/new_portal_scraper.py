"""
USER-CREATED JOB CONFIGURATION
Job Name: new_portal_scraper
Created: 2025-03-07
Status: AWAITING_DEVELOPER

Instructions for Developer:
This configuration was created by a user who needs automation for this portal.
Please implement the navigation logic in the navigate() function below.

User Requirements:
- Target: https://newportal.example.com/login
- Tokens needed: customer_id, report_type, start_date, end_date
- Credentials: Saved and encrypted in CONFIG below

Once implemented, change status to "READY" and test the job.
"""

CONFIG = {
    "job_name"       : "new_portal_scraper",
    "job_date"       : "2025-03-07",
    "target_url"     : "https://newportal.example.com/login",
    "tokens"         : {
        "customer_id": "",
        "report_type": "",
        "start_date" : "",
        "end_date"   : ""
    },
    "credentials"    : {
        "username": "user@example.com",
        "password": "encrypted_password"
    },
    "status"         : "AWAITING_DEVELOPER",
    "developer_notes": "This job needs navigation script implementation"
}


def navigate(context: dict) -> dict:
    """
    PLACEHOLDER: Developer needs to implement navigation logic

    Expected behavior:
    1. Login to https://newportal.example.com/login
    2. Navigate to the appropriate section
    3. Use tokens from CONFIG to filter/configure
    4. Download files or capture screenshots
    5. Return results

    Available in context:
    - helper: BrowserHelper (helper.go, helper.click, helper.type_text, etc.)
    - tokens: User's token values (merged from CONFIG)
    - credentials: User's username/password
    - logger: JobLogger (logger.info, logger.success, logger.error)
    - target_url: The URL to navigate to

    Example implementation (copy and uncomment to implement):

        helper = context["helper"]
        tokens = context["tokens"]
        creds = context["credentials"]
        logger = context["logger"]

        # Navigate to login
        helper.go(context["target_url"])
        helper.wait_for_page_load()

        # Login
        helper.type_text('//input[@id="username"]', creds["username"])
        helper.type_text('//input[@id="password"]', creds["password"])
        helper.click('//button[@type="submit"]')
        helper.wait_for_page_load()
        logger.success("Login successful")

        # Navigate to reports using tokens
        helper.click('//a[text()="Reports"]')
        helper.wait_for_page_load()

        helper.type_text('//input[@id="customer_id"]', tokens["customer_id"])
        helper.select_option('//select[@id="report_type"]', tokens["report_type"])
        helper.type_text('//input[@id="start_date"]', tokens["start_date"])
        helper.type_text('//input[@id="end_date"]', tokens["end_date"])

        # Download report
        helper.click('//button[@id="download"]')
        downloaded_file = helper.wait_for_download(timeout=60)
        logger.success("File downloaded", downloaded_file)

        # Take screenshot
        screenshot = helper.screenshot()
        logger.info("Screenshot captured", screenshot)

        return {"downloaded_file": downloaded_file, "screenshot": screenshot}
    """
    raise NotImplementedError(
        f"Job '{CONFIG['job_name']}' needs developer implementation. "
        f"See docstring above for user requirements and example code."
    )