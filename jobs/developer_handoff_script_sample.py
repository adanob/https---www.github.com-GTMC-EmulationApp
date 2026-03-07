"""
USER-CREATED JOB CONFIGURATION
Job Name: {job_name}
Created: {job_date}
Status: AWAITING_DEVELOPER

Instructions for Developer:
This configuration was created by a user who needs automation for this portal.
Please implement the navigation logic in the navigate() function below.

User Requirements:
- Target: {target_url}
- Tokens needed: {token_list}
- Credentials: Saved and encrypted in CONFIG below

Once implemented, change status to "READY" and test the job.
"""

CONFIG = {
    "job_name": "{job_name}",
    "job_date": "{job_date}",
    "target_url": "{target_url}",
    "tokens": {
{tokens_dict}
    },
    "credentials": {
{credentials_dict}
    },
    "status": "AWAITING_DEVELOPER",
    "developer_notes": "This job needs navigation script implementation"
}

def navigate(context: dict) -> dict:
    """
    PLACEHOLDER: Developer needs to implement navigation logic

    Expected behavior:
    1. Login to {target_url}
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

    Example implementation:

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

        # Use tokens to complete task
        # ... your navigation logic here ...

        # Take screenshot
        screenshot = helper.screenshot()
        logger.info("Screenshot captured", screenshot)

        return {"screenshot": screenshot}
    """
    raise NotImplementedError(
        f"Job '{CONFIG['job_name']}' needs developer implementation. "
        f"See docstring above for user requirements and example code."
    )