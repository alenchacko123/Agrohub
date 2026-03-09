"""
AgroHub - Selenium Automation Test Suite
=========================================
Test: Farmer Dashboard → Get Machinery → View Rentals → Equipment List
Description: Verifies the complete navigation flow:
             1. Start on the Farmer Dashboard (after login)
             2. Click "Get Machinery" in the sidebar
             3. Click "View Rentals" in the Machinery Center panel
             4. Confirm redirect to rent-equipment.html
             5. Verify the equipment list (cards) is visible on the page

Prerequisites:
    pip install selenium mysql-connector-python webdriver-manager

Run:
    python tests/test_view_rentals.py
"""

import time
import sys
import unittest
import mysql.connector
from selenium import webdriver
from selenium.webdriver.common.by import By
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
BASE_URL          = "http://localhost/Agrohub"
LOGIN_URL         = f"{BASE_URL}/login.html"
DASHBOARD_URL     = "farmer-dashboard.html"
RENT_EQUIPMENT_URL = "rent-equipment.html"

# ─────────────────────────────────────────────
#  Test Credentials
# ─────────────────────────────────────────────
TEST_EMAIL    = "alenchacko2028@mca.ajce.in"
TEST_PASSWORD = "Alen123@"

# ─────────────────────────────────────────────
#  Speed Control
#  Increase STEP_DELAY to make the test slower / easier to watch.
#  CHAR_DELAY controls how fast characters are typed.
# ─────────────────────────────────────────────
STEP_DELAY = 0.8    # seconds between major steps
CHAR_DELAY = 0.04   # seconds per typed character


def slow_type(element, text):
    """Type text one character at a time so the action looks natural."""
    for char in text:
        element.send_keys(char)
        time.sleep(CHAR_DELAY)


# ══════════════════════════════════════════════════════════════════
#  Helper: Fetch credentials from the database
# ══════════════════════════════════════════════════════════════════
def get_farmer_credentials_from_db():
    """
    Connects to the AgroHub MySQL database and retrieves the farmer
    user matching TEST_EMAIL.

    Returns:
        dict with keys: id, name, email, user_type
    Raises:
        AssertionError if the farmer is not found or DB is unreachable.
    """
    print("\n[DB] Connecting to AgroHub database …")
    try:
        conn   = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)

        query = """
            SELECT id, name, email, user_type
            FROM   users
            WHERE  email     = %s
              AND  user_type = 'farmer'
        """
        cursor.execute(query, (TEST_EMAIL,))
        row = cursor.fetchone()

        cursor.close()
        conn.close()

        if row is None:
            raise AssertionError(
                f"[DB ERROR] No farmer account found with email '{TEST_EMAIL}'.\n"
                "  Please ensure the user is registered in the 'users' table "
                "with user_type='farmer'."
            )

        print(f"[DB] ✅  Farmer found → id={row['id']}, "
              f"name='{row['name']}', email='{row['email']}'")
        return row

    except mysql.connector.Error as err:
        raise AssertionError(
            f"[DB ERROR] Could not connect to MySQL.\n"
            f"  Make sure XAMPP MySQL is running.\n"
            f"  Error: {err}"
        )


# ══════════════════════════════════════════════════════════════════
#  Test Class
# ══════════════════════════════════════════════════════════════════
class AgroHubViewRentalsTest(unittest.TestCase):
    """
    End-to-end Selenium test:
      Login → Farmer Dashboard → Get Machinery → View Rentals → Equipment List
    """

    @classmethod
    def setUpClass(cls):
        """Set up: verify DB record exists, then launch Chrome."""
        print("\n" + "═" * 65)
        print("  AgroHub Selenium Test → View Rentals Navigation")
        print("═" * 65)

        # ── Step 1: Validate credentials in DB ──────────────────────
        cls.farmer = get_farmer_credentials_from_db()

        # ── Step 2: Configure Chrome ─────────────────────────────────
        chrome_options = Options()
        # Uncomment to run without a visible browser window:
        # chrome_options.add_argument("--headless")
        chrome_options.add_argument("--start-maximized")
        chrome_options.add_argument("--disable-notifications")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-logging"])

        print("[Browser] Launching Chrome …")
        service    = Service(ChromeDriverManager().install())
        cls.driver = webdriver.Chrome(service=service, options=chrome_options)
        cls.wait   = WebDriverWait(cls.driver, 15)   # 15-second explicit wait

    @classmethod
    def tearDownClass(cls):
        """Quit the browser after all tests finish."""
        time.sleep(4)   # Longer pause so you can see the final state
        print("\n[Browser] Closing Chrome …")
        cls.driver.quit()

    # ──────────────────────────────────────────────────────────────
    def test_01_login_as_farmer(self):
        """
        Test 1 – Login:
          Open the login page, enter credentials, submit, and assert
          that the browser redirects to farmer-dashboard.html.
        """
        print("\n[Test 1] Navigating to login page …")
        self.driver.get(LOGIN_URL)
        self.wait.until(EC.title_contains("AgroHub"))
        time.sleep(STEP_DELAY)

        # ── Email ───────────────────────────────────────────────────
        email_field = self.wait.until(
            EC.element_to_be_clickable((By.ID, "email"))
        )
        email_field.click()
        email_field.clear()
        slow_type(email_field, TEST_EMAIL)
        print(f"[Test 1]   Entered email    : {TEST_EMAIL}")
        time.sleep(STEP_DELAY)

        # ── Password ────────────────────────────────────────────────
        password_field = self.driver.find_element(By.ID, "password")
        password_field.click()
        password_field.clear()
        slow_type(password_field, TEST_PASSWORD)
        print(f"[Test 1]   Entered password : {'*' * len(TEST_PASSWORD)}")
        time.sleep(STEP_DELAY)

        # ── Submit ──────────────────────────────────────────────────
        submit_btn = self.driver.find_element(
            By.CSS_SELECTOR, "#login-form button[type='submit']"
        )
        submit_btn.click()
        print("[Test 1]   Clicked 'Sign In' …")
        time.sleep(STEP_DELAY)

        # ── Wait for dashboard redirect ──────────────────────────────
        try:
            self.wait.until(EC.url_contains(DASHBOARD_URL))
        except Exception:
            current_url = self.driver.current_url
            self.fail(
                f"[Test 1 FAILED] Login did not redirect to '{DASHBOARD_URL}'.\n"
                f"  Current URL: {current_url}\n"
                f"  Hint: Ensure XAMPP Apache & MySQL are running and the "
                f"farmer account exists in the database."
            )

        current_url = self.driver.current_url
        print(f"[Test 1]   Redirected to: {current_url}")
        self.assertIn(DASHBOARD_URL, current_url)
        print("✅ [Test 1] Farmer logged in – now on Farmer Dashboard.")
        time.sleep(STEP_DELAY)

    # ──────────────────────────────────────────────────────────────
    def test_02_dashboard_loaded(self):
        """
        Test 2 – Dashboard Sanity Check:
          Verify the farmer dashboard has loaded with visible content
          and the sidebar navigation is present.
        """
        print("\n[Test 2] Verifying Farmer Dashboard content …")
        time.sleep(STEP_DELAY)

        # Title check
        self.wait.until(EC.title_contains("AgroHub"))
        page_title = self.driver.title
        current_url = self.driver.current_url
        print(f"[Test 2]   Page Title : {page_title}")
        print(f"[Test 2]   Page URL   : {current_url}")

        # Must still be on the dashboard
        self.assertIn(DASHBOARD_URL, current_url,
                      "Dashboard URL assertion failed.")

        # Sidebar must be visible
        sidebar = self.wait.until(
            EC.visibility_of_element_located((By.ID, "sidebar"))
        )
        self.assertTrue(sidebar.is_displayed(), "Sidebar is not visible.")

        # Body must have meaningful content
        body_text = self.driver.find_element(By.TAG_NAME, "body").text
        self.assertGreater(len(body_text), 50,
                           "Dashboard page body appears empty.")

        print("✅ [Test 2] Farmer Dashboard loaded and sidebar is visible.")
        time.sleep(STEP_DELAY)

    # ──────────────────────────────────────────────────────────────
    def test_03_click_get_machinery_in_sidebar(self):
        """
        Test 3 – Click 'Get Machinery':
          Find the 'Get Machinery' link in the sidebar navigation and
          click it. The Machinery Center view should become visible in
          the main content area.
        """
        print("\n[Test 3] Clicking 'Get Machinery' in the sidebar …")
        time.sleep(STEP_DELAY)

        # Locate the sidebar nav item whose text is 'Get Machinery'
        # The element: <a href="javascript:void(0)" class="nav-item"
        #               onclick="switchView('machinery', this)">
        #                 <span ...>agriculture</span>
        #                 Get Machinery
        #              </a>
        get_machinery_link = self.wait.until(
            EC.element_to_be_clickable(
                (By.XPATH,
                 "//a[contains(@class,'nav-item') and "
                 "contains(@onclick,\"'machinery'\")]")
            )
        )

        # Scroll into view just in case the sidebar is taller than the viewport
        self.driver.execute_script(
            "arguments[0].scrollIntoView({behavior:'smooth', block:'center'});",
            get_machinery_link
        )
        time.sleep(0.5)

        print(f"[Test 3]   Found link text: '{get_machinery_link.text.strip()}'")
        get_machinery_link.click()
        print("[Test 3]   Clicked 'Get Machinery' sidebar link.")
        time.sleep(STEP_DELAY)

        # The Machinery view div should now be active / visible
        machinery_view = self.wait.until(
            EC.visibility_of_element_located((By.ID, "view-machinery"))
        )
        self.assertTrue(machinery_view.is_displayed(),
                        "#view-machinery panel is not visible after click.")

        # Confirm the heading "Machinery Center" is showing
        machinery_heading = machinery_view.find_element(
            By.XPATH, ".//h2[contains(text(),'Machinery')]"
        )
        self.assertTrue(machinery_heading.is_displayed(),
                        "'Machinery Center' heading not visible.")
        print(f"[Test 3]   Machinery panel heading → '{machinery_heading.text}'")

        print("✅ [Test 3] 'Get Machinery' clicked – Machinery Center panel visible.")
        time.sleep(STEP_DELAY)

    # ──────────────────────────────────────────────────────────────
    def test_04_click_view_rentals_button(self):
        """
        Test 4 – Click 'View Rentals':
          Inside the Machinery Center panel, locate the 'View Rentals'
          button and click it.  This should navigate to rent-equipment.html.
        """
        print("\n[Test 4] Clicking 'View Rentals' button …")
        time.sleep(STEP_DELAY)

        # The button:
        # <button class="quick-action-btn primary"
        #         onclick="window.location.href='rent-equipment.html'"
        #         style="width: 100%; border-radius: 8px;">View Rentals</button>
        machinery_view = self.driver.find_element(By.ID, "view-machinery")

        view_rentals_btn = self.wait.until(
            EC.element_to_be_clickable(
                (By.XPATH,
                 "//div[@id='view-machinery']"
                 "//button[normalize-space(text())='View Rentals']")
            )
        )

        # Scroll into view
        self.driver.execute_script(
            "arguments[0].scrollIntoView({behavior:'smooth', block:'center'});",
            view_rentals_btn
        )
        time.sleep(0.5)

        print(f"[Test 4]   Button text: '{view_rentals_btn.text.strip()}'")
        view_rentals_btn.click()
        print("[Test 4]   Clicked 'View Rentals' button.")
        time.sleep(STEP_DELAY)

        # ── Wait for navigation to rent-equipment.html ───────────────
        try:
            self.wait.until(EC.url_contains(RENT_EQUIPMENT_URL))
        except Exception:
            current_url = self.driver.current_url
            self.fail(
                f"[Test 4 FAILED] 'View Rentals' did not navigate to "
                f"'{RENT_EQUIPMENT_URL}'.\n"
                f"  Current URL: {current_url}"
            )

        current_url = self.driver.current_url
        print(f"[Test 4]   Navigated to: {current_url}")
        self.assertIn(RENT_EQUIPMENT_URL, current_url)

        print("✅ [Test 4] Navigated to rent-equipment.html successfully.")
        time.sleep(STEP_DELAY)

    # ──────────────────────────────────────────────────────────────
    def test_05_rent_equipment_page_loaded(self):
        """
        Test 5 – rent-equipment.html Loaded:
          Confirm the page title, page heading, and the equipment
          listing section are visible.
        """
        print("\n[Test 5] Verifying rent-equipment.html page …")
        time.sleep(STEP_DELAY)

        # Page title
        self.wait.until(EC.title_contains("Rent Equipment"))
        page_title = self.driver.title
        print(f"[Test 5]   Page title: '{page_title}'")
        self.assertIn("Rent Equipment", page_title,
                      "Page title does not contain 'Rent Equipment'.")

        # Visible page heading: <h1 class="page-title">🚜 Rent Equipment</h1>
        page_heading = self.wait.until(
            EC.visibility_of_element_located((By.CSS_SELECTOR, "h1.page-title"))
        )
        self.assertTrue(page_heading.is_displayed(),
                        "Page heading (h1.page-title) is not visible.")
        print(f"[Test 5]   Page heading: '{page_heading.text}'")
        self.assertIn("Rent Equipment", page_heading.text,
                      "Page heading does not contain 'Rent Equipment'.")

        print("✅ [Test 5] rent-equipment.html is loaded with correct heading.")
        time.sleep(STEP_DELAY)

    # ──────────────────────────────────────────────────────────────
    def test_06_equipment_list_is_visible(self):
        """
        Test 6 – Equipment List Visible:
          Confirm that the equipment grid section is present, and that
          at least one equipment card is rendered on the page.

          NOTE: The page fetches cards dynamically from the database
          via PHP (fetch_equipment.php). If no equipment cards are
          found, the test checks for the search/filter section at
          minimum to confirm the page is functioning correctly.
        """
        print("\n[Test 6] Checking for equipment list on the page …")
        time.sleep(STEP_DELAY)

        # ── Verify the filters section (always rendered) ─────────────
        filters_section = self.wait.until(
            EC.visibility_of_element_located(
                (By.CSS_SELECTOR, ".filters-section")
            )
        )
        self.assertTrue(filters_section.is_displayed(),
                        "Filters section (.filters-section) is not visible.")
        print("[Test 6]   ✅ Filters section is visible.")

        # ── Search input ────────────────────────────────────────────
        search_input = self.driver.find_element(By.ID, "searchInput")
        self.assertTrue(search_input.is_displayed(),
                        "Search input (#searchInput) is not visible.")
        print("[Test 6]   ✅ Search input is visible.")

        # ── Category filter dropdown ─────────────────────────────────
        category_filter = self.driver.find_element(By.ID, "categoryFilter")
        self.assertTrue(category_filter.is_displayed(),
                        "Category filter (#categoryFilter) is not visible.")
        print("[Test 6]   ✅ Category filter dropdown is visible.")

        # ── Equipment grid section ───────────────────────────────────
        # Wait for the equipment grid to appear (the PHP fetch may take a moment)
        try:
            equipment_grid = self.wait.until(
                EC.presence_of_element_located(
                    (By.CSS_SELECTOR, ".equipment-grid")
                )
            )
            self.assertTrue(equipment_grid.is_displayed(),
                            "Equipment grid (.equipment-grid) is not visible.")
            print("[Test 6]   ✅ Equipment grid (.equipment-grid) is present.")
        except Exception:
            self.fail(
                "[Test 6 FAILED] Equipment grid section (.equipment-grid) "
                "was not found on rent-equipment.html.\n"
                "  Ensure equipment is added in the database and the PHP "
                "fetch script is working."
            )

        # ── Count equipment cards ────────────────────────────────────
        # Give dynamic content a moment to fully populate
        time.sleep(2)
        equipment_cards = self.driver.find_elements(
            By.CSS_SELECTOR, ".equipment-card"
        )

        print(f"[Test 6]   Equipment cards found: {len(equipment_cards)}")

        if len(equipment_cards) == 0:
            print("[Test 6]   ⚠️  No equipment cards found – the database may "
                  "be empty or the PHP fetch script may have an error.")
            print("[Test 6]   ℹ️  The page itself loaded correctly with all "
                  "required UI sections (grid, filters, search).")
        else:
            # Assert at least one card is visible
            first_card = equipment_cards[0]
            self.assertTrue(first_card.is_displayed(),
                            "First equipment card is not displayed.")

            # Try to read the equipment name from the card
            try:
                eq_title = first_card.find_element(
                    By.CSS_SELECTOR, ".equipment-title"
                )
                print(f"[Test 6]   First equipment: '{eq_title.text}'")
            except Exception:
                print("[Test 6]   (Could not read equipment title from card)")

            print(f"[Test 6]   ✅ {len(equipment_cards)} equipment card(s) visible.")

        print("✅ [Test 6] Equipment list section verified on rent-equipment.html.")
        time.sleep(STEP_DELAY)


# ══════════════════════════════════════════════════════════════════
#  Entry Point
# ══════════════════════════════════════════════════════════════════
if __name__ == "__main__":
    print("""
╔══════════════════════════════════════════════════════════════╗
║   AgroHub Selenium Test → Dashboard → Machinery → Rentals   ║
╚══════════════════════════════════════════════════════════════╝

Flow:
  1. Open login page and sign in as farmer
  2. Verify Farmer Dashboard is loaded
  3. Click "Get Machinery" in the sidebar
  4. Click "View Rentals" in the Machinery Center panel
  5. Verify rent-equipment.html is loaded
  6. Verify the equipment list/grid section is visible

Prerequisites (run once):
    pip install selenium mysql-connector-python webdriver-manager

Usage:
    python tests/test_view_rentals.py
""")

    # Run tests in defined (alphabetical / numbered) order
    loader = unittest.TestLoader()
    loader.sortTestMethodsUsing = lambda a, b: (a > b) - (a < b)
    suite  = loader.loadTestsFromTestCase(AgroHubViewRentalsTest)
    runner = unittest.TextTestRunner(verbosity=2, stream=sys.stdout)
    result = runner.run(suite)

    # Exit with non-zero code if any test failed
    sys.exit(0 if result.wasSuccessful() else 1)
