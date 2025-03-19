import mysql.connector
import subprocess
import time
import logging
import re
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
        cursor.execute("SELECT id, router_name, ip_address FROM routers")
        routers = cursor.fetchall()
        cursor.close()
        connection.close()
        return routers
    except mysql.connector.Error as e:
        logger.error(f"Database error while fetching routers: {e}")
        return []

def ping_router(ip):
    """Ping the router 3 times and return the average latency in ms."""
    try:
        total_latency = 0
        successful_pings = 0

        for _ in range(3):  # Ping 3 times
            result = subprocess.run(["ping", "-c", "1", ip], capture_output=True, text=True)
            match = re.search(r"time=(\d+\.\d+)", result.stdout)

            if match:
                total_latency += float(match.group(1))
                successful_pings += 1
            time.sleep(1)  # Small delay between pings

        if successful_pings > 0:
            return total_latency / successful_pings  # Return average latency
    except Exception as e:
        logger.error(f"Ping error for {ip}: {e}")

    return None  # Return None if all pings fail

def save_status_to_db(router_id, status, latency):
    """Insert or update router status in MySQL database when changes occur."""
    try:
        connection = mysql.connector.connect(**db_config)
        cursor = connection.cursor()

        # Get the current time in the configured timezone
        current_time = datetime.now(TIMEZONE)

        query = """
        INSERT INTO router_status (router_id, status, latency_ms, timestamp)
        VALUES (%s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE status = VALUES(status), latency_ms = VALUES(latency_ms), timestamp = VALUES(timestamp);
        """

        cursor.execute(query, (router_id, status, latency, current_time))
        connection.commit()
        cursor.close()
        connection.close()
        logger.info(f"Router {router_id} status '{status}' updated with latency {latency} ms at {current_time}")
    except mysql.connector.Error as e:
        logger.error(f"Database error: {e}")

def handle_router(router):
    """Process a single router's ping and status update."""
    global last_status

    router_id, router_ip = router["id"], router["ip_address"]
    latency = ping_router(router_ip)
    status = "up" if latency is not None else "down"

    # Get the previous state of the router
    last_state = last_status.get(router_id, {"status": None, "latency": None})

    # Check if status or latency has changed
    if status != last_state["status"] or (latency is not None and abs(latency - last_state["latency"]) > 1.0):
        save_status_to_db(router_id, status, latency)
        last_status[router_id] = {"status": status, "latency": latency}

def main():
    """Main function to continuously monitor routers using multithreading."""
    while True:
        routers = get_all_routers()
        if not routers:
            logger.warning("No routers found in database. Retrying in 60 seconds...")
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

        # Ping every 60 seconds but insert data only if changes occur
        time.sleep(2)

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        logger.info("KeyboardInterrupt caught, script will continue running.")
