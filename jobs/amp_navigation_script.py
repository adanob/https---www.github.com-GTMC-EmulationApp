"""
AMP Portal - Sample Working Navigation Script

This is a complete, working navigation script that can be uploaded
and used immediately. It demonstrates the correct structure.

Created: 2025-03-07
Status: READY
"""

CONFIG = {
    "job_name": "amp_sample_working",
    "job_date": "2025-03-07",
    "target_url": "https://amp.chargebacks911.com/login",
    "tokens": {
        "date_from": "03/01/2025",
        "date_to": "03/07/2025"
    },
    "credentials": {
        "username": "user@example.com",
        "password": "encrypted_password_here"
    },
    "status": "READY"
}

def navigate(context: dict) -> dict:
    """
    Navigate AMP portal and download emulation data

    This function contains the actual navigation logic.
    When uploaded, this script can be selected and run immediately.
    """
    helper = context["helper"]
    tokens = context["tokens"]
    creds = context["credentials"]
    logger = context["logger"]

    # Navigate to login page
    helper.go(context["target_url"])
    helper.wait_for_page_load()
    logger.info("Page loaded", context["target_url"])

    # Login
    helper.type_text('//*[@id="email"]', creds.get("username", ""))
    helper.type_text('//*[@id="password"]', creds.get("password", ""))
    helper.click('//*[@id="login-button"]')
    helper.wait_for_page_load()
    logger.success("Login submitted")

    # Navigate to Emulation Downloads page
    helper.go("https://amp.chargebacks911.com/emulation")
    helper.wait_for_page_load()
    logger.info("Navigated to Emulation Downloads")

    # Enter date range
    helper.type_text(
        '//*[@name="startDate"]',
        tokens.get("date_from", "03/01/2025"),
        clear_first=True
    )

    helper.type_text(
        '//*[@name="endDate"]',
        tokens.get("date_to", "03/07/2025"),
        clear_first=True
    )
    logger.info("Date range entered", f"{tokens.get('date_from')} to {tokens.get('date_to')}")

    # Click the Go button
    helper.click('//*[@id="go-button"]')
    helper.wait_for_page_load()
    logger.success("Go button clicked — report loading")

    # Take screenshot
    screenshot = helper.screenshot()
    logger.info("Screenshot captured", screenshot)

    return {"screenshot": screenshot}


# This is what makes it a valid standalone script
# The CONFIG dictionary provides metadata
# The navigate() function contains the logic
# No NotImplementedError - this script is READY to run