Mark absent scheduler

This folder contains a script to mark absent attendance for shifts whose end time has passed.

Script: mark_absent.php
Purpose: Idempotently insert an 'Absent' attendance row for shifts that ended without any time_in/time_out recorded.

Scheduling (Windows Task Scheduler)
You can run the script at specific times (like 12:00 and 18:00) or run it hourly. Because the script now only processes today's shifts, running it every hour is safe and will keep Absent status updated.

GUI steps (hourly):
1. Open Task Scheduler.
2. Create a basic task. Name: HR3 Mark Absent Hourly
3. Trigger: Daily -> Advanced Settings -> Repeat task every: 1 hour, for a duration of: 1 day.
4. Action: Start a program.
   - Program/script: C:\\php\\php.exe   (or your PHP CLI executable path)
   - Add arguments: -f "C:\\xampp\\htdocs\\hr3_microfinance\\scripts\\mark_absent.php"
   - Start in: C:\\xampp\\htdocs\\hr3_microfinance\\scripts
5. Save. Ensure the task runs with an account that has access to the webroot and DB.

Command-line example (create a daily task that repeats hourly):
```powershell
schtasks /Create /SC DAILY /TN "HR3 Mark Absent Hourly" /TR "\"C:\\xampp\\php\\php.exe\" -f \"C:\\xampp\\htdocs\\hr3_microfinance\\scripts\\mark_absent.php\"" /ST 00:00 /RI 60 /F
```

Notes:
- The task will start at 00:00 and repeat every 60 minutes (adjust /ST and /RI as needed).
- Ensure the account running the task has proper permissions.

Notes
- The script uses server timezone (set in PHP ini or at top of script). Adjust if necessary.
- You can test by running: php mark_absent.php from the scripts dir.
- The script is safe to run multiple times; it won't insert duplicate absent rows.
