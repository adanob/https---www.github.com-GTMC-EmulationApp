"""
USER JOB: {job_name}
Based on: {base_script}
Created: {job_date}
Status: READY
"""

CONFIG = {
    "job_name": "{job_name}",
    "job_date": "{job_date}",
    "target_url": "{target_url}",
    "base_script": "{base_script}",
    "tokens": {},
    "credentials": {},
    "status": "READY"
}

# Import the base navigation logic
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', 'scripts'))

# Try to import the base script
try:
    from {base_script_module} import navigate as base_navigate
except ImportError as e:
    raise ImportError(
        f"Failed to import navigation script '{base_script_module}'. "
        f"Make sure the file 'scripts/{base_script_module}.py' exists and has a 'navigate' function. "
        f"Error: {e}"
    )

def navigate(context: dict) -> dict:
    """Execute base script with user configuration"""
    # Inject user's configuration into context
    context["tokens"].update(CONFIG["tokens"])
    context["target_url"] = CONFIG["target_url"]
    if CONFIG["credentials"]:
        context["credentials"] = CONFIG["credentials"]

    # Call the base navigation script
    return base_navigate(context)