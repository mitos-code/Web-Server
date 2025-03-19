import paramiko
import logging
import pytz
import re
import mysql.connector
from datetime import datetime
import argparse

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Timezone
TIMEZONE = pytz.timezone("Asia/Kuala_Lumpur")

# Parse command-line arguments
parser = argparse.ArgumentParser(description="Fetch MikroTik router interfaces.")
parser.add_argument("--router_name", required=True, help="Router name")
parser.add_argument("--ip_address", required=True, help="Router IP address")
parser.add_argument("--ssh_username", help="SSH username")
parser.add_argument("--ssh_password", help="SSH password")
args = parser.parse_args()

ROUTER_NAME = args.router_name
SSH_IP = args.ip_address
SSH_USERNAME = args.ssh_username
SSH_PASSWORD = args.ssh_password

# MySQL connection settings
MYSQL_HOST = "localhost"  # Change to your MySQL host if needed
MYSQL_USER = "root"  # Change to your MySQL username
MYSQL_PASSWORD = "Amirhmbm_2004"  # Change to your MySQL password if needed
MYSQL_DATABASE = "web_server"  # Change to your database name

def fetch_interfaces(ip_address, username, password):
    """SSH into the MikroTik router and retrieve interface details."""
    try:
        client = paramiko.SSHClient()
        client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

        # Attempt connection based on provided credentials
        if username and password:
            client.connect(ip_address, username=username, password=password, timeout=5)
        elif username and not password:
            client.connect(ip_address, username=username, timeout=5, allow_agent=False, look_for_keys=False)
        else:
            # If no username and password, connect without credentials
            client.connect(ip_address, timeout=5, allow_agent=False, look_for_keys=False)

        command = "/interface/print"
        stdin, stdout, stderr = client.exec_command(command)
        output = stdout.read().decode().strip()
        client.close()

        print(f"\n🔹 Raw MikroTik output from {ip_address}:\n{output}\n")  # Debugging

        return parse_interfaces(output)
    except Exception as e:
        logger.error(f"❌ SSH error for {ip_address}: {e}")
        return []

def parse_interfaces(output):
    """Parse the MikroTik interface output and extract all interface names."""
    interfaces = []
    pattern = re.compile(r'\d+\s+[RS]*\s*([\w-]+)')  # Regex to capture interface names

    for line in output.split("\n"):
        match = pattern.search(line)
        if match:
            interfaces.append(match.group(1))

    return interfaces

def insert_interfaces_to_db(ip_address, router_name, interfaces):
    """Insert or update the retrieved interface details into the MySQL database."""
    try:
        connection = mysql.connector.connect(
            host=MYSQL_HOST,
            user=MYSQL_USER,
            password=MYSQL_PASSWORD,
            database=MYSQL_DATABASE
        )

        cursor = connection.cursor()

        timestamp = datetime.now(TIMEZONE).strftime("%Y-%m-%d %H:%M:%S")
        ether_interfaces = ', '.join(interfaces)

        # Insert or update the data in the database
        query = """
        INSERT INTO router_interfaces (ip_address, router_name, timestamp, interface)
        VALUES (%s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE
        router_name = VALUES(router_name),
        timestamp = VALUES(timestamp),
        interface = VALUES(interface)
        """
        cursor.execute(query, (ip_address, router_name, timestamp, ether_interfaces))

        # Commit the transaction
        connection.commit()
        logger.info(f"✅ Data inserted/updated into router_interfaces for {ip_address}")

    except mysql.connector.Error as err:
        logger.error(f"❌ MySQL error: {err}")
    finally:
        cursor.close()
        connection.close()

def process_router():
    """Fetch interface details from the router and insert into DB."""
    interfaces = fetch_interfaces(SSH_IP, SSH_USERNAME, SSH_PASSWORD)

    if interfaces:
        print(f"✅ Retrieved interfaces for {SSH_IP}: {', '.join(interfaces)}")
        insert_interfaces_to_db(SSH_IP, ROUTER_NAME, interfaces)
    else:
        print(f"⚠ No interfaces found for {SSH_IP}")

def main():
    """Process the router and insert interfaces into MySQL."""
    process_router()

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        logger.info("🛑 Script stopped by user.")
