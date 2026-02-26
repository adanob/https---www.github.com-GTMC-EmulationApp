"""
DriverFactory  - Create browser instances based on user selection.

Supported drivers:
  - selenium   (Chrome via chromedriver)
  - playwright (Chromium via playwright)

The user selects their driver from the Dashboard UI dropdown.
No hardcoded Config file  - paths are resolved from environment
variables or sensible defaults.
"""

import os
from typing import Optional


class DriverFactory:
    """Instantiates browser drivers based on the payload's `driver` field."""

    SELENIUM = "selenium"
    PLAYWRIGHT = "playwright"

    @staticmethod
    def create(driver_type: str = "selenium",
               headless: bool = True,
               download_dir: Optional[str] = None):
        """
        Build and return a browser driver instance.

        :param driver_type:  "selenium" or "playwright"
        :param headless:     Run without a visible window
        :param download_dir: Where downloaded files land (defaults to /tmp/downloads)
        :return:             A driver instance (WebDriver or Playwright Page)
        """
        download_dir = download_dir or os.path.join("/tmp", "downloads")
        os.makedirs(download_dir, exist_ok=True)

        if driver_type == DriverFactory.SELENIUM:
            return DriverFactory._create_selenium(headless, download_dir)
        elif driver_type == DriverFactory.PLAYWRIGHT:
            return DriverFactory._create_playwright(headless, download_dir)
        else:
            raise ValueError(f"Unsupported driver: {driver_type}")

    # ── Selenium ───────────────────────────────────────────────

    @staticmethod
    def _create_selenium(headless: bool, download_dir: str):
        from selenium import webdriver
        from selenium.webdriver.chrome.options import Options
        from selenium.webdriver.chrome.service import Service

        options = Options()
        if headless:
            options.add_argument("--headless=new")
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--start-maximized")
        options.add_argument("--window-size=1920,1080")

        # Only disable GPU on headless (causes visibility issues on Windows desktop)
        if headless:
            options.add_argument("--disable-gpu")

        # Configure downloads
        prefs = {
            "download.default_directory": download_dir,
            "download.prompt_for_download": False,
            "download.directory_upgrade": True,
            "safebrowsing.enabled": True,
        }
        options.add_experimental_option("prefs", prefs)

        # Resolve binary / driver from environment or defaults
        chrome_binary = os.getenv("CHROME_BINARY")
        chrome_driver = os.getenv("CHROME_DRIVER")

        if chrome_binary:
            options.binary_location = chrome_binary

        service = Service(executable_path=chrome_driver) if chrome_driver else Service()
        driver = webdriver.Chrome(service=service, options=options)
        return driver

    # ── Playwright ─────────────────────────────────────────────

    @staticmethod
    def _create_playwright(headless: bool, download_dir: str):
        from playwright.sync_api import sync_playwright

        pw = sync_playwright().start()
        browser = pw.chromium.launch(headless=headless)
        context = browser.new_context(accept_downloads=True)
        context._download_dir = download_dir  # stash for helper reference
        page = context.new_page()
        # Attach references so the caller can close cleanly
        page._pw = pw
        page._browser = browser
        return page