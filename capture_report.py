from selenium import webdriver
from selenium.webdriver.chrome.options import Options
import time

options = Options()
options.add_argument("--headless")
options.add_argument("--window-size=1000,1200")
driver = webdriver.Chrome(options=options)

driver.get("file:///c:/xampp/htdocs/Agrohub/tmp_report.html")
time.sleep(2)
driver.save_screenshot("C:/Users/LENOVO/.gemini/antigravity/brain/97e6a1f1-c3e1-476d-b9f5-2336be77c7d3/test_report.png")
driver.quit()
