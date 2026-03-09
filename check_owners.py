import mysql.connector

try:
    conn = mysql.connector.connect(
        host="127.0.0.1",
        user="root",
        password="", # XAMPP default
        database="agrohub",
        port=3306
    )
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT name, email, user_type FROM users WHERE user_type='owner'")
    results = cursor.fetchall()
    print(f"Found {len(results)} owners:")
    for row in results:
        print(row)
    cursor.close()
    conn.close()
except Exception as e:
    print(f"Error: {e}")
