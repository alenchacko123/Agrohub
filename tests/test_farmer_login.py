"""
AgroHub - Selenium Automation Test Suite
=========================================
Test: Farmer Login Test
Description: Verifies that a farmer can log in using credentials from the database
             and is successfully redirected to the farmer dashboard page.

Prerequisites:
    pip install selenium mysql-connector-python webdriver-manager

Run:
    python tests/test_farmer_login.py
"""

import time
import sys
import unittest
import mysql.connector
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager


# ─────────────────────────────────────────────
#  Database Configuration (from php/config.php)
# ─────────────────────────────────────────────
DB_CONFIG = {
    "host":     "127.0.0.1",
    "user":     "root",
    "password": "",           # XAMPP default has no password
    "database": "agrohub",
    "port":     3306,
}

# ─────────────────────────────────────────────
#  Application URLs
# ─────────────────────────────────────────────
BASE_URL       = "http://localhost/Agrohub"
LANDING_URL    = f"{BASE_URL}/landingpage.html"
LOGIN_URL      = f"{BASE_URL}/login.html"
DASHBOARD_URL  = "farmer-dashboard.html"   # relative – we check URL contains this

# ─────────────────────────────────────────────
#  Test Credentials (as given by user)
# ─────────────────────────────────────────────
TEST_EMAIL    = "alenchacko2028@mca.ajce.in"
TEST_PASSWORD = "Alen123@"

# ─────────────────────────────────────────────
#  Speed Control
#  Increase STEP_DELAY to make the test slower.
#  CHAR_DELAY controls typing speed per character.
# ─────────────────────────────────────────────
STEP_DELAY = 0.5    # seconds to pause between major steps
CHAR_DELAY = 0.03   # seconds between each typed character


def slow_type(element, text):
    """Type text into an element one character at a time so it looks natural."""
    for char in text:
        element.send_keys(char)
        time.sleep(CHAR_DELAY)


# ══════════════════════════════════════════════════════════════════
#  Helper: Fetch credentials from the database and verify they exist
# ══════════════════════════════════════════════════════════════════
def get_farmer_credentials_from_db():
    """
    Connects to the AgroHub MySQL database and retrieves the
    farmer user record matching TEST_EMAIL.

    Returns:
        dict with keys: id, name, email, user_type
        or raises an AssertionError if the user is not found.
    """
    print("\n[DB] Connecting to AgroHub database …")
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)

        query = """
            SELECT id, name, email, user_type
            FROM   users
            WHERE  email = %s
              AND  user_type = 'farmer'
        """
        cursor.execute(query, (TEST_EMAIL,))
        row = cursor.fetchone()

        cursor.close()
        conn.close()

        if row is None:
            raise AssertionError(
                f"[DB ERROR] No farmer account found with email '{TEST_EMAIL}'.\n"
                "  Please ensure the user is registered in the 'users' table with user_type='farmer'."
            )

        print(f"[DB] ✅  Farmer found in DB → id={row['id']}, name='{row['name']}', "
              f"email='{row['email']}', user_type='{row['user_type']}'")
        return row

    except mysql.connector.Error as err:
        raise AssertionError(
            f"[DB ERROR] Could not connect to MySQL database.\n"
            f"  Make sure XAMPP MySQL is running.\n"
            f"  Error: {err}"
        )


# ══════════════════════════════════════════════════════════════════
#  Test Class
# ══════════════════════════════════════════════════════════════════
class AgroHubFarmerLoginTest(unittest.TestCase):
    """Selenium test case for AgroHub Farmer Login → Dashboard redirect."""

    @classmethod
    def setUpClass(cls):
        """Set up: verify DB record exists, then launch Chrome."""
        print("\n" + "═" * 60)
        print("  AgroHub Selenium Test → Farmer Login")
        print("═" * 60)

        # ── Step 1: Validate credentials exist in DB ──────────────
        cls.farmer = get_farmer_credentials_from_db()

        # ── Step 2: Configure Chrome (headful so you can watch) ───
        chrome_options = Options()
        # Uncomment the next line to run headlessly (no browser window):
        # chrome_options.add_argument("--headless")
        chrome_options.add_argument("--start-maximized")
        chrome_options.add_argument("--disable-notifications")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-logging"])

        print("[Browser] Launching Chrome …")
        service = Service(ChromeDriverManager().install())
        cls.driver = webdriver.Chrome(service=service, options=chrome_options)
        cls.wait   = WebDriverWait(cls.driver, 15)   # 15-second explicit wait

    @classmethod
    def tearDownClass(cls):
        """Tear down: quit the browser after all tests."""
        time.sleep(4)   # Longer pause so you can see the final state
        print("\n[Browser] Closing Chrome …")
        cls.driver.quit()

    # ──────────────────────────────────────────────────────────────
    def test_00_open_landing_page_and_navigate_to_login(self):
        """
        Test 0 – Landing Page:
          1. Open the AgroHub landing page
          2. Pause so you can see it
          3. Click the 'Login' link in the navbar to go to login.html
        """
        print("\n[Test 0] Opening AgroHub Landing page ...")
        time.sleep(STEP_DELAY)

        # ── Go to Landing Page ────────────────────────────────────
        self.driver.get(LANDING_URL)
        self.wait.until(EC.title_contains("AgroHub"))
        time.sleep(STEP_DELAY)   # pause – let you see the landing page

        page_title = self.driver.title
        print(f"[Test 0]   Landing page title: '{page_title}'")
        self.assertIn("AgroHub", page_title)
        time.sleep(STEP_DELAY)

        # ── Click the 'Login' link in the navbar ────────────────────
        print("[Test 0]   Clicking 'Login' in the navbar ...")
        login_link = self.wait.until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, "a.nav-link[href='login.html']"))
        )
        login_link.click()
        time.sleep(STEP_DELAY)   # pause – let you see the transition

        # ── Confirm we arrived at login.html ───────────────────────
        self.wait.until(EC.url_contains("login.html"))
        current_url = self.driver.current_url
        print(f"[Test 0]   Navigated to: {current_url}")
        self.assertIn("login.html", current_url)
        print("[Test 0] ✅  Successfully navigated from Landing page to Login page.")
        time.sleep(STEP_DELAY)

    # ──────────────────────────────────────────────────────────────
    def test_01_open_login_page(self):
        """Test 1 – Verify the Login page title (already on login.html from test_00)."""
        print("\n[Test 1] Verifying login page title …")
        time.sleep(STEP_DELAY)

        # Already on login.html from test_00 – just verify the title
        self.wait.until(EC.title_contains("AgroHub"))
        time.sleep(STEP_DELAY)

        page_title = self.driver.title
        print(f"[Test 1] Page title: '{page_title}'")

        self.assertIn(
            "AgroHub", page_title,
            f"Expected 'AgroHub' in page title but got: '{page_title}'"
        )
        print("[Test 1] ✅  Login page verified successfully.")


    # ──────────────────────────────────────────────────────────────
    def test_02_login_form_elements_present(self):
        """Test 2 – Verify that email, password, and submit button exist."""
        print("\n[Test 2] Checking form elements …")
        time.sleep(STEP_DELAY)

        email_field    = self.wait.until(EC.presence_of_element_located((By.ID, "email")))
        time.sleep(STEP_DELAY / 2)
        password_field = self.driver.find_element(By.ID, "password")
        time.sleep(STEP_DELAY / 2)
        submit_btn     = self.driver.find_element(By.CSS_SELECTOR, "#login-form button[type='submit']")
        time.sleep(STEP_DELAY / 2)

        self.assertTrue(email_field.is_displayed(),    "Email field not visible")
        self.assertTrue(password_field.is_displayed(), "Password field not visible")
        self.assertTrue(submit_btn.is_displayed(),     "Submit button not visible")

        print("[Test 2] ✅  All form elements are present and visible.")
        time.sleep(STEP_DELAY)

    # ──────────────────────────────────────────────────────────────
    def test_03_farmer_login_and_dashboard_redirect(self):
        """
        Test 3 – Core Login Test:
          1. Enter credentials fetched from DB (alenchacko2028@mca.ajce.in / Alen123@)
          2. Submit the login form
          3. Assert successful redirect to farmer-dashboard.html
        """
        print("\n[Test 3] Performing farmer login …")
        time.sleep(STEP_DELAY)

        # Already on login.html from test_00 – no need to reload
        self.wait.until(EC.presence_of_element_located((By.ID, "email")))
        time.sleep(STEP_DELAY / 2)

        # ── Click on email field ────────────────────────────────────
        email_field = self.driver.find_element(By.ID, "email")
        email_field.click()
        time.sleep(STEP_DELAY / 2)

        # ── Type email slowly, character by character ───────────────
        email_field.clear()
        slow_type(email_field, TEST_EMAIL)
        print(f"[Test 3]   Entered email: {TEST_EMAIL}")
        time.sleep(STEP_DELAY)   # pause – let you read what was typed

        # ── Click on password field ─────────────────────────────────
        password_field = self.driver.find_element(By.ID, "password")
        password_field.click()
        time.sleep(STEP_DELAY / 2)

        # ── Type password slowly, character by character ────────────
        password_field.clear()
        slow_type(password_field, TEST_PASSWORD)
        print(f"[Test 3]   Entered password: {'*' * len(TEST_PASSWORD)}")
        time.sleep(STEP_DELAY)   # pause – let you see the filled form

        # ── Click Submit ────────────────────────────────────────────
        submit_btn = self.driver.find_element(
            By.CSS_SELECTOR, "#login-form button[type='submit']"
        )
        submit_btn.click()
        print("[Test 3]   Clicked 'Sign In' button …")
        time.sleep(STEP_DELAY)   # pause – let you see the loading state

        # ── Wait for redirect to dashboard ──────────────────────────
        try:
            self.wait.until(EC.url_contains(DASHBOARD_URL))
        except Exception:
            current_url = self.driver.current_url
            try:
                notif_elem = self.driver.find_element(
                    By.CSS_SELECTOR, ".google-notification"
                )
                error_msg = notif_elem.text.strip()
            except Exception:
                error_msg = "(no visible notification found)"

            self.fail(
                f"[Test 3 FAILED] Login did not redirect to '{DASHBOARD_URL}'.\n"
                f"  Current URL : {current_url}\n"
                f"  Page message: {error_msg}\n"
                f"  Hint: Make sure XAMPP Apache & MySQL are running and "
                f"  the farmer account exists in the database."
            )

        current_url = self.driver.current_url
        print(f"[Test 3]   Redirected to: {current_url}")

        self.assertIn(
            DASHBOARD_URL, current_url,
            f"Expected URL to contain '{DASHBOARD_URL}' but got: '{current_url}'"
        )
        print("[Test 3] ✅  Login successful! Redirected to Farmer Dashboard.")
        time.sleep(STEP_DELAY)   # pause – let you see the dashboard

    # ──────────────────────────────────────────────────────────────
    def test_04_dashboard_page_loads(self):
        """Test 4 – Verify the Farmer Dashboard page loads correctly."""
        print("\n[Test 4] Verifying dashboard page content …")
        time.sleep(STEP_DELAY)

        # Wait for the page title to reflect the dashboard
        self.wait.until(EC.title_contains("AgroHub"))
        time.sleep(STEP_DELAY)   # pause – let you look around the dashboard

        page_title = self.driver.title
        page_url   = self.driver.current_url

        print(f"[Test 4]   Dashboard URL  : {page_url}")
        print(f"[Test 4]   Dashboard title: {page_title}")
        time.sleep(STEP_DELAY)

        # The dashboard URL must contain farmer-dashboard.html
        self.assertIn(
            DASHBOARD_URL, page_url,
            "Test must run after a successful login redirect."
        )

        # Body should have content (page loaded, not blank)
        body_text = self.driver.find_element(By.TAG_NAME, "body").text
        self.assertGreater(
            len(body_text), 50,
            "Dashboard page body appears to be empty – page may not have loaded."
        )

        print("[Test 4] ✅  Farmer Dashboard loaded successfully.")
        time.sleep(STEP_DELAY)   # final pause before browser closes




# ══════════════════════════════════════════════════════════════════
#  Entry Point
# ══════════════════════════════════════════════════════════════════
if __name__ == "__main__":
    print("""
╔══════════════════════════════════════════════════════════╗
║       AgroHub Selenium Automation – Farmer Login         ║
╚══════════════════════════════════════════════════════════╝

Prerequisites (run once):
    pip install selenium mysql-connector-python webdriver-manager

Usage:
    python tests/test_farmer_login.py
""")

    # Run tests in defined order, with verbose output
    loader = unittest.TestLoader()
    loader.sortTestMethodsUsing = lambda a, b: (a > b) - (a < b)
    suite  = loader.loadTestsFromTestCase(AgroHubFarmerLoginTest)
    runner = unittest.TextTestRunner(verbosity=2, stream=sys.stdout)
    result = runner.run(suite)

    # Exit with non-zero code if any test failed
    sys.exit(0 if result.wasSuccessful() else 1)
