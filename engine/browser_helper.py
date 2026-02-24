"""
BrowserHelper  - Convenience layer for navigation scripts.

Provides a driver-agnostic API for the most common browser actions:
  click, type, screenshot, wait, select, download, get page source, etc.

Navigation scripts call these helpers instead of raw Selenium / Playwright
APIs, making them portable across driver choices.
"""

import os
import time
from typing import Optional, List


class BrowserHelper:
    """Wraps a Selenium WebDriver or Playwright Page with a unified API."""

    def __init__(self, driver, driver_type: str = "selenium", download_dir: str = "/tmp/downloads"):
        self.driver = driver
        self.driver_type = driver_type
        self.download_dir = download_dir

    # ── Navigation ─────────────────────────────────────────────

    def go(self, url: str):
        """Navigate to a URL."""
        if self.driver_type == "selenium":
            self.driver.get(url)
        else:
            self.driver.goto(url)

    def current_url(self) -> str:
        if self.driver_type == "selenium":
            return self.driver.current_url
        else:
            return self.driver.url

    # ── Element Interaction ────────────────────────────────────

    def click(self, xpath: str, timeout: int = 10):
        """Click an element located by XPath."""
        if self.driver_type == "selenium":
            from selenium.webdriver.common.by import By
            from selenium.webdriver.support.ui import WebDriverWait
            from selenium.webdriver.support import expected_conditions as EC
            el = WebDriverWait(self.driver, timeout).until(
                EC.element_to_be_clickable((By.XPATH, xpath))
            )
            el.click()
        else:
            self.driver.locator(f"xpath={xpath}").click(timeout=timeout * 1000)

    def type_text(self, xpath: str, text: str, clear_first: bool = True, timeout: int = 10):
        """Type text into an input field located by XPath."""
        if self.driver_type == "selenium":
            from selenium.webdriver.common.by import By
            from selenium.webdriver.support.ui import WebDriverWait
            from selenium.webdriver.support import expected_conditions as EC
            el = WebDriverWait(self.driver, timeout).until(
                EC.presence_of_element_located((By.XPATH, xpath))
            )
            if clear_first:
                el.clear()
            el.send_keys(text)
        else:
            loc = self.driver.locator(f"xpath={xpath}")
            if clear_first:
                loc.fill(text, timeout=timeout * 1000)
            else:
                loc.type(text, timeout=timeout * 1000)

    def select_option(self, xpath: str, value: str, timeout: int = 10):
        """Select a dropdown option by visible text."""
        if self.driver_type == "selenium":
            from selenium.webdriver.common.by import By
            from selenium.webdriver.support.ui import WebDriverWait
            from selenium.webdriver.support import expected_conditions as EC
            from selenium.webdriver.support.select import Select
            el = WebDriverWait(self.driver, timeout).until(
                EC.presence_of_element_located((By.XPATH, xpath))
            )
            Select(el).select_by_visible_text(value)
        else:
            self.driver.locator(f"xpath={xpath}").select_option(label=value, timeout=timeout * 1000)

    def get_text(self, xpath: str, timeout: int = 10) -> str:
        """Get the visible text of an element."""
        if self.driver_type == "selenium":
            from selenium.webdriver.common.by import By
            from selenium.webdriver.support.ui import WebDriverWait
            from selenium.webdriver.support import expected_conditions as EC
            el = WebDriverWait(self.driver, timeout).until(
                EC.presence_of_element_located((By.XPATH, xpath))
            )
            return el.text
        else:
            return self.driver.locator(f"xpath={xpath}").inner_text(timeout=timeout * 1000)

    def get_attribute(self, xpath: str, attribute: str, timeout: int = 10) -> Optional[str]:
        """Get an attribute value from an element."""
        if self.driver_type == "selenium":
            from selenium.webdriver.common.by import By
            from selenium.webdriver.support.ui import WebDriverWait
            from selenium.webdriver.support import expected_conditions as EC
            el = WebDriverWait(self.driver, timeout).until(
                EC.presence_of_element_located((By.XPATH, xpath))
            )
            return el.get_attribute(attribute)
        else:
            return self.driver.locator(f"xpath={xpath}").get_attribute(attribute, timeout=timeout * 1000)

    def element_exists(self, xpath: str) -> bool:
        """Check if an element exists on the page (no wait)."""
        if self.driver_type == "selenium":
            from selenium.webdriver.common.by import By
            return len(self.driver.find_elements(By.XPATH, xpath)) > 0
        else:
            return self.driver.locator(f"xpath={xpath}").count() > 0

    def elements(self, xpath: str) -> List:
        """Return all matching elements."""
        if self.driver_type == "selenium":
            from selenium.webdriver.common.by import By
            return self.driver.find_elements(By.XPATH, xpath)
        else:
            return self.driver.locator(f"xpath={xpath}").all()

    # ── Waits ──────────────────────────────────────────────────

    def wait(self, seconds: float):
        """Simple sleep."""
        time.sleep(seconds)

    def wait_for_page_load(self, timeout: int = 30):
        """Wait until the page finishes loading."""
        if self.driver_type == "selenium":
            from selenium.webdriver.support.ui import WebDriverWait
            WebDriverWait(self.driver, timeout).until(
                lambda d: d.execute_script("return document.readyState") == "complete"
            )
        else:
            self.driver.wait_for_load_state("networkidle", timeout=timeout * 1000)

    # ── Screenshots ────────────────────────────────────────────

    def screenshot(self, filepath: Optional[str] = None) -> str:
        """Take a screenshot. Returns the file path."""
        filepath = filepath or os.path.join(self.download_dir, f"screenshot_{int(time.time())}.png")
        if self.driver_type == "selenium":
            self.driver.save_screenshot(filepath)
        else:
            self.driver.screenshot(path=filepath)
        return filepath

    # ── Page Source ─────────────────────────────────────────────

    def page_source(self) -> str:
        """Return the current page HTML."""
        if self.driver_type == "selenium":
            return self.driver.page_source
        else:
            return self.driver.content()

    # ── Downloads ──────────────────────────────────────────────

    def wait_for_download(self, timeout: int = 60) -> Optional[str]:
        """Wait for a file to appear in the download directory."""
        end = time.time() + timeout
        while time.time() < end:
            files = [f for f in os.listdir(self.download_dir)
                     if not f.endswith(".crdownload") and not f.endswith(".tmp")]
            if files:
                return os.path.join(self.download_dir, sorted(files)[-1])
            time.sleep(1)
        return None

    # ── Cleanup ────────────────────────────────────────────────

    def quit(self):
        """Close the browser and release resources."""
        try:
            if self.driver_type == "selenium":
                self.driver.quit()
            else:
                self.driver._browser.close()
                self.driver._pw.stop()
        except Exception:
            pass
