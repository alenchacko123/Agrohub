"""
AgroHub - Selenium Automation Test Suite
=========================================
Test: Skill Center Test
Description: Verifies that a farmer can navigate to the Skill Center, 
             click Watch Videos, select the first video, and start learning.

Prerequisites:
    pip install selenium mysql-connector-python webdriver-manager

Run:
    python tests/test_skill_center.py
"""

import time
import sys
import unittest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

# ─────────────────────────────────────────────
#  Application URLs
# ─────────────────────────────────────────────
BASE_URL       = "http://localhost/Agrohub"
LOGIN_URL      = f"{BASE_URL}/login.html"

# ─────────────────────────────────────────────
#  Test Credentials
# ─────────────────────────────────────────────
TEST_EMAIL    = "alenchacko2028@mca.ajce.in"
TEST_PASSWORD = "Alen123@"

# ─────────────────────────────────────────────
#  Speed Control
# ─────────────────────────────────────────────
STEP_DELAY = 1.0


class AgroHubSkillCenterTest(unittest.TestCase):
    """Selenium test case for Farm Skill Center."""

    @classmethod
    def setUpClass(cls):
        print("\n" + "═" * 60)
        print("  AgroHub Selenium Test → Skill Center")
        print("═" * 60)

        chrome_options = Options()
        chrome_options.add_argument("--start-maximized")
        chrome_options.add_argument("--disable-notifications")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-logging"])

        print("[Browser] Launching Chrome …")
        service = Service(ChromeDriverManager().install())
        cls.driver = webdriver.Chrome(service=service, options=chrome_options)
        cls.wait   = WebDriverWait(cls.driver, 15)

    @classmethod
    def tearDownClass(cls):
        time.sleep(3)
        print("\n[Browser] Closing Chrome …")
        cls.driver.quit()

    def test_01_login_to_dashboard(self):
        """Test 1: Login and reach the farmer dashboard."""
        print("\n[Test 1] Logging in to Farmer Dashboard ...")
        self.driver.get(LOGIN_URL)
        self.wait.until(EC.presence_of_element_located((By.ID, "email")))
        
        # Login
        self.driver.find_element(By.ID, "email").send_keys(TEST_EMAIL)
        self.driver.find_element(By.ID, "password").send_keys(TEST_PASSWORD)
        self.driver.find_element(By.CSS_SELECTOR, "#login-form button[type='submit']").click()
        
        # Wait for dashboard
        self.wait.until(EC.url_contains("farmer-dashboard.html"))
        print("[Test 1] ✅ Successfully logged in and reached dashboard.")
        time.sleep(STEP_DELAY)

    def test_02_navigate_to_skill_center(self):
        """Test 2: Click on Skill Center from sidebar."""
        print("\n[Test 2] Navigating to Skill Center ...")
        
        # Click Skill Center menu item
        skill_center_link = self.wait.until(
            EC.element_to_be_clickable((By.XPATH, "//a[contains(@class, 'nav-item') and contains(., 'Skill Center')]"))
        )
        # Using javascript click since element might be obscured by floating elements
        self.driver.execute_script("arguments[0].click();", skill_center_link)
        
        # Verify the Skills view is active
        self.wait.until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "#view-skills.active"))
        )
        print("[Test 2] ✅ Skill Center section is now active.")
        time.sleep(STEP_DELAY)

    def test_03_click_watch_videos(self):
        """Test 3: Click 'Watch Videos' button."""
        print("\n[Test 3] Clicking 'Watch Videos' button ...")
        
        # Click "Watch Videos" button within the skills view
        watch_videos_btn = self.wait.until(
            EC.element_to_be_clickable((By.XPATH, "//div[@id='view-skills']//button[contains(text(), 'Watch Videos')]"))
        )
        self.driver.execute_script("arguments[0].scrollIntoView({behavior: 'smooth', block: 'center'});", watch_videos_btn)
        time.sleep(1) # wait for scroll
        self.driver.execute_script("arguments[0].click();", watch_videos_btn)
        
        # Wait for tutorials page to load
        self.wait.until(EC.url_contains("tutorials.html"))
        print("[Test 3] ✅ Navigated to Tutorials page.")
        time.sleep(STEP_DELAY)

    def test_04_click_first_video_and_start_learning(self):
        """Test 4: Click the 1st video and 'Start Learning'."""
        print("\n[Test 4] Selecting the first video and starting to learn ...")
        
        # Wait for videos to load in the grid
        first_video_card = self.wait.until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, ".tutorial-grid .tutorial-card"))
        )
        
        self.driver.execute_script("arguments[0].scrollIntoView({behavior: 'smooth', block: 'center'});", first_video_card)
        time.sleep(1) # wait for scroll
        
        # Find the "Start Learning" button inside the first card
        start_btn = first_video_card.find_element(By.CSS_SELECTOR, ".start-btn")
        self.driver.execute_script("arguments[0].click();", start_btn)
        
        # Wait for the modal to appear and become active
        self.wait.until(
            EC.presence_of_element_located((By.CSS_SELECTOR, "#tutorialModal.active"))
        )
        
        # Verify the video frame is present
        video_frame = self.driver.find_element(By.ID, "videoFrame")
        self.assertTrue(video_frame.is_displayed(), "Video frame is not displayed")
        
        print("[Test 4] ✅ First video opened successfully. Learning started.")
        time.sleep(3) # Leave it open slightly longer to watch before finishing

if __name__ == "__main__":
    # Run tests in defined order
    loader = unittest.TestLoader()
    loader.sortTestMethodsUsing = lambda a, b: (a > b) - (a < b)
    suite  = loader.loadTestsFromTestCase(AgroHubSkillCenterTest)
    runner = unittest.TextTestRunner(verbosity=2, stream=sys.stdout)
    result = runner.run(suite)
    sys.exit(0 if result.wasSuccessful() else 1)
