"""
AMP Portal Navigation Script
This is a base script that can be reused with different job configurations.

Created: 2025-03-07
Status: READY
"""

def navigate(context: dict) -> dict:
    """
    Navigate AMP portal and download emulation data.

    Args:
        context (dict): Contains helper, tokens, credentials, logger, target_url

    Returns:
        dict: Results including screenshots, downloaded files, etc.
    """
    # Extract tools from context
    helper = context["helper"]
    tokens = context["tokens"]
    credentials = context["credentials"]
    logger = context["logger"]
    target_url = context["target_url"]

    # Navigate to login page
    helper.go(target_url)
    helper.wait_for_page_load()
    logger.info(f"Navigated to {target_url}")

    # Login
    helper.type_text('//*[@id="email"]', credentials.get("username", ""))
    helper.type_text('//*[@id="password"]', credentials.get("password", ""))
    helper.click('//*[@id="login-button"]')
    helper.wait_for_page_load()
    logger.success("Login successful")

    # Navigate to Emulation Downloads
    helper.go("https://amp.chargebacks911.com/emulation")
    helper.wait_for_page_load()
    logger.info("Navigated to Emulation Downloads")

    # Enter date range from tokens
    date_from = tokens.get("date_from", "01/01/2025")
    date_to = tokens.get("date_to", "01/31/2025")

    helper.type_text('//*[@name="startDate"]', date_from, clear_first=True)
    helper.type_text('//*[@name="endDate"]', date_to, clear_first=True)
    logger.info(f"Date range set: {date_from} to {date_to}")

    # Click Go button
    helper.click('//*[@id="go-button"]')
    helper.wait_for_page_load()
    logger.success("Report loading")

    # Take screenshot
    screenshot = helper.screenshot()
    logger.info(f"Screenshot captured: {screenshot}")

    # Return results
    return {
        "screenshot": screenshot,
        "date_from": date_from,
        "date_to": date_to,
        "status": "completed"
    }


# This is a BASE SCRIPT - it defines the navigate() function
# Job files will import this function and call it with user-specific configuration