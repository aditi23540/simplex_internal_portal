# ldap_auth.py
from ldap3 import Server, Connection, NTLM, ALL
from ldap3.core.exceptions import LDAPException, LDAPBindError, LDAPInvalidCredentialsResult # Import LDAPInvalidCredentialsResult
import sys
import html # To escape potentially problematic characters in output
import datetime # For logging timestamp

# Define a simple log file path (ensure the web server has write permissions here)
LOG_FILE_PATH = "ldap_debug.log" 

def log_debug_message(message):
    """Appends a message to the debug log file."""
    try:
        with open(LOG_FILE_PATH, "a") as f:
            f.write(f"{datetime.datetime.now()}: {message}\n")
    except Exception:
        # If logging fails, we don't want to crash the script
        pass

def authenticate_and_get_empcode(username, password):
    """
    Authenticates a user against LDAP using NTLM and retrieves their employeeID.

    Args:
        username (str): The user's sAMAccountName.
        password (str): The user's password.

    Returns:
        str: A string indicating the result.
    """
    domain = 'simplexengg.in'
    server_address = 'ldap://simplexengg.in' 
    base_dn = 'DC=simplexengg,DC=in'
    full_username = f'{domain}\\{username}' 

    conn = None # Initialize conn to None for the finally block

    try:
        server = Server(server_address, get_info=ALL, connect_timeout=5)
        conn = Connection(server, user=full_username, password=password, authentication=NTLM, auto_bind=True, raise_exceptions=True)
        
        search_filter = f'(sAMAccountName={html.escape(username)})'
        conn.search(search_base=base_dn, 
                    search_filter=search_filter, 
                    attributes=['employeeID'])

        if conn.entries and hasattr(conn.entries[0], 'employeeID') and conn.entries[0].employeeID.value is not None:
            empcode = conn.entries[0].employeeID.value
            # conn.unbind() # Unbind will be handled in finally
            return f"SUCCESS:{html.escape(str(empcode))}"
        else:
            # conn.unbind() # Unbind will be handled in finally
            return "AUTH_FAILED_NO_EMPCODE"

    # Catch both LDAPBindError and LDAPInvalidCredentialsResult for bad credentials
    except (LDAPBindError, LDAPInvalidCredentialsResult) as cred_err:
        log_debug_message(f"Credential Error caught for user {username}. Type: {type(cred_err)}, Error: {cred_err}")
        return "AUTH_FAILED_BIND_ERROR"
    except LDAPException as ldap_err:
        # Log the specific LDAPException that occurred (if it's not a credential error)
        log_debug_message(f"Generic LDAPException caught for user {username}. Type: {type(ldap_err)}, Error: {ldap_err}")
        return "LDAP_ERROR"
    except Exception as e:
        log_debug_message(f"Unexpected Python error for user {username}. Type: {type(e)}, Error: {e}")
        return "PYTHON_SCRIPT_ERROR"
    finally:
        if conn and conn.bound:
            try:
                conn.unbind()
            except Exception as unbind_e:
                log_debug_message(f"Error during unbind for user {username}: {unbind_e}")


if __name__ == "__main__":
    if len(sys.argv) != 3:
        # No need to log this, it's a usage error for the script itself
        print("USAGE_ERROR_INVALID_ARGS")
        sys.exit(1)

    username_arg = sys.argv[1]
    password_arg = sys.argv[2]
    
    result = authenticate_and_get_empcode(username_arg, password_arg)
    print(result)
