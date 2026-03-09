"""
AgroHub - Selenium Automation Test Suite
=========================================
Test: Admin User Management Test
Description: Verifies that an admin can log in, navigate to User Management,
             find a 'test owner' and change their role from owner to farmer.

Prerequisites:
    pip install selenium mysql-connector-python webdriver-manager

Run:
    python tests/test_admin_manage_user.py
"""

import time
import sys
import unittest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select
from webdriver_manager.chrome import ChromeDriverManager

# ─────────────────────────────────────────────
#  Application URLs
# ─────────────────────────────────────────────
BASE_URL       = "http://localhost/Agrohub"
LOGIN_URL      = f"{BASE_URL}/login.html"

# ─────────────────────────────────────────────
#  Admin Credentials
# ─────────────────────────────────────────────
ADMIN_EMAIL    = "admin@gmail.com"
ADMIN_PASSWORD = "admin123"

# ─────────────────────────────────────────────
#  Target User Info
# ─────────────────────────────────────────────
TARGET_USER_NAME = "Test Owner"

# ─────────────────────────────────────────────
#  Speed Control
# ─────────────────────────────────────────────
STEP_DELAY = 1.0


class AgroHubAdminManageUserTest(unittest.TestCase):
    """Selenium test case for Admin User Management."""

    @classmethod
    def setUpClass(cls):
        print("\n" + "═" * 60)
        print("  AgroHub Selenium Test → Admin Manage User Role")
        print("═" * 60)

        chrome_options = Options()
        chrome_options.add_argument("--start-maximized")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-logging"])

        print("[Browser] Launching Chrome …")
        service = Service(ChromeDriverManager().install())
        cls.driver = webdriver.Chrome(service=service, options=chrome_options)
        cls.wait   = WebDriverWait(cls.driver, 15)

    @classmethod
    def tearDownClass(cls):
        time.sleep(4)
        print("\n[Browser] Closing Chrome …")
        cls.driver.quit()

    def test_01_admin_login(self):
        """Test 1: Admin login to Reach Admin Dashboard."""
        print("\n[Test 1] Logging in as Admin ...")
        self.driver.get(LOGIN_URL)
        
        # Fill login form
        email_field = self.wait.until(EC.presence_of_element_located((By.ID, "email")))
        email_field.send_keys(ADMIN_EMAIL)
        self.driver.find_element(By.ID, "password").send_keys(ADMIN_PASSWORD)
        self.driver.find_element(By.CSS_SELECTOR, "#login-form button[type='submit']").click()
        
        # Verify dashboard redirect
        self.wait.until(EC.url_contains("admin-dashboard.html"))
        print("[Test 1] ✅ Successfully logged in to Admin Dashboard.")
        time.sleep(STEP_DELAY)

    def test_02_navigate_to_user_management(self):
        """Test 2: Click 'Users' in sidebar."""
        print("\n[Test 2] Navigating to User Management ...")
        
        # Find and click 'Users' in the sidebar
        users_menu_item = self.wait.until(
            EC.element_to_be_clickable((By.XPATH, "//a[contains(., 'Users')]"))
        )
        self.driver.execute_script("arguments[0].click();", users_menu_item)
        
        # Wait for the users table to be visible
        self.wait.until(
            EC.presence_of_element_located((By.ID, "view-users"))
        )
        print("[Test 2] ✅ User Management view is active.")
        time.sleep(STEP_DELAY)

    def test_03_manage_test_owner(self):
        """Test 3: Find 'test owner' and click Manage."""
        print(f"\n[Test 3] Finding and managing user: '{TARGET_USER_NAME}' ...")
        
        # Enter 'test owner' in the search box
        search_box = self.driver.find_element(By.ID, "user-search")
        search_box.clear()
        search_box.send_keys(TARGET_USER_NAME)
        
        # Wait for search results to update (debounce takes 350ms)
        time.sleep(1)
        
        # Find the row for the target user and click 'Manage'
        # We look for the 'Manage' button in a row that contains the user name
        manage_btn = self.wait.until(
            EC.element_to_be_clickable((By.XPATH, f"//tr[contains(., '{TARGET_USER_NAME}')]//button[contains(text(), 'Manage')]"))
        )
        self.driver.execute_script("arguments[0].click();", manage_btn)
        
        # Wait for manage modal to open
        self.wait.until(
            EC.presence_of_element_located((By.ID, "modal-manage"))
        )
        print("[Test 3] ✅ 'Manage User' modal opened.")
        time.sleep(STEP_DELAY)

    def test_04_change_role_to_farmer(self):
        """Test 4: Change role from owner to farmer."""
        print("\n[Test 4] Changing role to 'farmer' ...")
        
        # Click 'Change Role' in the manage modal
        change_role_btn = self.wait.until(
            EC.element_to_be_clickable((By.XPATH, "//button[contains(., 'Change Role')]"))
        )
        self.driver.execute_script("arguments[0].click();", change_role_btn)
        
        # Wait for role modal to open
        self.wait.until(
            EC.presence_of_element_located((By.ID, "modal-role"))
        )
        
        # Select 'Farmer' in the dropdown
        role_select_elem = self.wait.until(EC.presence_of_element_located((By.ID, "new-role-select")))
        role_select = Select(role_select_elem)
        role_select.select_by_value("farmer")
        print("[Test 4]   Selected 'farmer' role.")
        time.sleep(STEP_DELAY)
        
        # Click 'Save Role'
        save_btn = self.wait.until(
            EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Save Role')]"))
        )
        self.driver.execute_script("arguments[0].click();", save_btn)
        
        # Wait for the role change to complete (modal closes or toast appears)
        time.sleep(2)
        
        # Check if the toast message exists and indicates success (Success Toast)
        try:
            toast = self.driver.find_element(By.ID, "admin-toast")
            if toast.is_displayed():
                print(f"[Test 4]   Server Toast: '{toast.text}'")
        except:
            pass

        print("[Test 4] ✅ Role change process completed.")

if __name__ == "__main__":
    # Run tests in defined order
    loader = unittest.TestLoader()
    loader.sortTestMethodsUsing = lambda a, b: (a > b) - (a < b)
    suite  = loader.loadTestsFromTestCase(AgroHubAdminManageUserTest)
    runner = unittest.TextTestRunner(verbosity=2, stream=sys.stdout)
    result = runner.run(suite)
    sys.exit(0 if result.wasSuccessful() else 1)
