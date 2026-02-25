"""
AMP Portal - Emulation Downloads Navigation Script

Logs into the Chargebacks911 Automation Management Portal,
navigates to the Emulation Downloads page, enters a date range,
and clicks Go to load the report.
"""


def navigate(context: dict) -> dict:
    helper = context["helper"]
    tokens = context["tokens"]
    creds  = context["credentials"]
    logger = context["logger"]

    # -- Navigate to the login page ----------------------------------
    helper.go(context["target_url"])
    helper.wait_for_page_load()
    logger.info("Page loaded", context["target_url"])

    # -- Login -------------------------------------------------------
    helper.type_text('//*[@id="email"]', creds.get("username", ""))
    helper.type_text('//*[@id="password"]', creds.get("password", ""))
    helper.click('//*[@id="login-button"]')
    helper.wait_for_page_load()
    logger.success("Login submitted")

    # -- Navigate to the Emulation Downloads page --------------------
    helper.go("https://amp.chargebacks911.com/emulation")
    helper.wait_for_page_load()
    logger.info("Navigated to Emulation Downloads")

    # -- Enter date range --------------------------------------------
    # Clear and fill the start-date picker
    helper.type_text(
        '//*[@id="dp1771995320190"]',
        tokens.get("date_from", "02/01/2025"),
        clear_first=True
    )

    # Clear and fill the end-date picker
    helper.type_text(
        '//*[@id="dp1771995320191"]',
        tokens.get("date_to", "02/24/2025"),
        clear_first=True
    )
    logger.info("Date range entered", f"{tokens.get('date_from')} to {tokens.get('date_to')}")

    # -- Click the Go button -----------------------------------------
    helper.click(
        '/html/body/div[1]/div[2]/div[1]/div/div/div[1]/div[2]/div[1]/form/div/div[1]/div/input[3]'
    )
    helper.wait_for_page_load()
    logger.success("Go button clicked — report loading")

    # -- Screenshot the results --------------------------------------
    screenshot = helper.screenshot()
    logger.info("Screenshot captured", screenshot)

    return {"screenshot": screenshot}