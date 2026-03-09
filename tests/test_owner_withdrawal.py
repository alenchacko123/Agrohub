import unittest
import time
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
from webdriver_manager.chrome import ChromeDriverManager

class AgroHubOwnerWithdrawalTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        chrome_options = Options()
        # chrome_options.add_argument("--headless")  # Uncomment for headless mode
        chrome_options.add_argument("--window-size=1920,1080")
        chrome_options.add_argument("--disable-notifications")
        
        cls.driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
        cls.wait = WebDriverWait(cls.driver, 20)
        cls.base_url = "http://localhost/Agrohub"

    @classmethod
    def tearDownClass(cls):
        cls.driver.quit()

    def test_owner_withdrawal(self):
        driver = self.driver
        wait = self.wait

        # 1. Login as owner
        print("Navigating to AgroHub login...")
        driver.get(f"{self.base_url}/login.html")
        
        print("Logging in as owner...")
        wait.until(EC.presence_of_element_located((By.ID, "email"))).send_keys("alenchacko2004@gmail.com")
        driver.find_element(By.ID, "password").send_keys("Alen123@")
        
        # Click login button
        login_btn = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
        login_btn.click()

        # 2. Wait for owner dashboard and navigate to Earnings
        print("Waiting for owner dashboard...")
        # Check if we are on the owner dashboard by looking for the sidebar or dashboard title
        wait.until(EC.url_contains("owner-dashboard.html"))
        
        print("Navigating to Earnings...")
        # Find the "Earnings" nav item. Based on HTML it's a link with text "Earnings"
        # We'll use XPath to be sure
        earnings_nav = wait.until(EC.element_to_be_clickable((By.XPATH, "//a[contains(., 'Earnings')]")))
        # Sometimes standard click fails if overlapping, using JS click to be safe
        driver.execute_script("arguments[0].click();", earnings_nav)

        # 3. Wait for Earnings Modal and click "Withdraw to Bank"
        print("Opening Earnings modal...")
        wait.until(EC.presence_of_element_located((By.ID, "earningsModal")))
        
        print("Clicking 'Withdraw to Bank'...")
        # The button is inside the modal body
        withdraw_btn = wait.until(EC.element_to_be_clickable((By.XPATH, "//button[contains(text(), 'Withdraw to Bank')]")))
        driver.execute_script("arguments[0].click();", withdraw_btn)

        # 4. Fill Withdrawal Form
        print("Filling withdrawal form...")
        wait.until(EC.visibility_of_element_located((By.ID, "withdrawalModal")))
        
        wait.until(EC.presence_of_element_located((By.ID, "wd-amount"))).send_keys("1000")
        driver.find_element(By.ID, "wd-account").send_keys("7873635262")
        driver.find_element(By.ID, "wd-ifsc").send_keys("IDIB0001223")
        
        # Beneficiary Name
        name_field = driver.find_element(By.ID, "wd-name")
        name_field.clear() # Clear potential pre-filled name
        name_field.send_keys("alen chacko")

        # 5. Process Withdrawal
        print("Processing withdrawal...")
        # The button might be disabled if validation hasn't run yet, so we trigger an input event or pause
        time.sleep(1) 
        
        submit_btn = driver.find_element(By.ID, "wd-submit-btn")
        # If the button is still disabled, it might be due to validation logic.
        # Let's ensure it's clickable
        wait.until(EC.element_to_be_clickable((By.ID, "wd-submit-btn")))
        driver.execute_script("arguments[0].click();", submit_btn)

        # 6. Verify Success
        print("Waiting for payout confirmation...")
        # Check for the success modal
        wait.until(EC.visibility_of_element_located((By.ID, "paymentSuccessModal")))
        
        # Check for success message or specific text if "Withdrawal Initiated" is shown
        success_title = driver.find_element(By.CSS_SELECTOR, "#paymentSuccessModal h2").text
        self.assertIn("Initiated", success_title)
        
        print("payments completed")

if __name__ == "__main__":
    unittest.main()
