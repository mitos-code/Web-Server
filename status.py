import mysql.connector
import subprocess
import time
import logging
import threading
import pytz
from datetime import datetime

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Database config
db_config = {
    "host": "localhost",
    "user": "root",
    "password": "Amirhmbm_2004",  # Change if needed
    "database": "web_server"
}

# Store last known router status to detect changes
last_status = {}

# Timezone configuration (Change to your preferred timezone)
TIMEZONE = pytz.timezone("Asia/Kuala_Lumpur")  # Example for Malaysia

def get_all_routers():
    """Fetch all routers from the database."""
    try:
        connection = mysql.connector.connect(**db_config)
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT router_name, ip_address FROM routers")
        routers = cursor.fetchall()
        cursor.close()
        connection.close()
        return routers
    except mysql.connector.Error as e:
        logger.error(f"Database error while fetching routers: {e}")
        return []

def ping_router(ip):
    """Ping the router to check if it's reachable."""
    try:
        result = subprocess.run(["ping", "-c", "1", ip], capture_output=True, text=True)
        return result.returncode == 0  # Returns True if ping is successful, False otherwise
    except Exception as e:
        logger.error(f"Ping error for {ip}: {e}")
        return False

def save_status_to_db(router_name, ip_address, status):
    """Insert or update router status in MySQL database when changes occur."""
    try:
        connection = mysql.connector.connect(**db_config)
        cursor = connection.cursor()

        # Get the current time in the configured timezone
        current_time = datetime.now(TIMEZONE)

        query = """
        INSERT INTO router_status (router_name, ip_address, status, timestamp)
        VALUES (%s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
        status = VALUES(status),
        timestamp = VALUES(timestamp);
        """

        cursor.execute(query, (router_name, ip_address, status, current_time))
        connection.commit()

        logger.info(f"✅ Inserted/Updated -> Router: {router_name}, IP: {ip_address}, Status: {status}, Time: {current_time}")

        cursor.close()
        connection.close()
    except mysql.connector.Error as e:
        logger.error(f"❌ Database error while inserting/updating: {e}")

def save_status_history_to_db():
    """Insert router status history in MySQL database."""
    try:
        connection = mysql.connector.connect(**db_config)
        cursor = connection.cursor()

        # Get the current time in the configured timezone
        current_time = datetime.now(TIMEZONE)

        for router_ip, status_info in last_status.items():
            status = status_info['status']
            cursor.execute("SELECT router_name FROM routers WHERE ip_address = %s", (router_ip,))
            router_name = cursor.fetchone()[0]

            query = """
            INSERT INTO status_history (router_name, ip_address, status, timestamp)
            VALUES (%s, %s, %s, %s)
            """

            cursor.execute(query, (router_name, router_ip, status, current_time))

        connection.commit()

        logger.info(f"✅ Inserted status history for {len(last_status)} routers at {current_time}")

        cursor.close()
        connection.close()
    except mysql.connector.Error as e:
        logger.error(f"❌ Database error while inserting status history: {e}")

def handle_router(router):
    """Process a single router's ping and status update."""
    global last_status

    router_name, router_ip = router["router_name"], router["ip_address"]
    is_up = ping_router(router_ip)
    status = "up" if is_up else "down"

    # Print result to terminal
    print(f"🔍 Router: {router_name} | IP: {router_ip} | Status: {status}")
    # Get the previous state of the router
    last_state = last_status.get(router_ip, {"status": None})

    # Check if status has changed
    if status != last_state["status"]:
        save_status_to_db(router_name, router_ip, status)
        last_status[router_ip] = {"status": status}

def main():
    """Main function to continuously monitor routers using multithreading."""
    while True:
        routers = get_all_routers()
        if not routers:
            logger.warning("⚠ No routers found in database. Retrying in 60 seconds...")
            time.sleep(60)
            continue

        threads = []

        for router in routers:
            # Start a new thread for each router
            thread = threading.Thread(target=handle_router, args=(router,))
            thread.start()
            threads.append(thread)

        # Wait for all threads to finish
        for thread in threads:
            thread.join()

        # Insert data into status_history every 1 minute
        save_status_history_to_db()

        # Ping every 60 seconds but insert data only if changes occur
        time.sleep(60)

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        logger.info("🛑 Script stopped by user.")
