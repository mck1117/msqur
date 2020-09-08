
API hosted at https://rusefi.com/online/

## Auth
input: auth token
output: 
{
  "getUserByToken": {
    "ID": "2",
    "NAME": "AndreyB"
  }
}

https://rusefi.com/online/api.php?method=getUserByToken&rusefi_token=xxx-xxx-xxx

# tune list

https://rusefi.com/online/api.php?method=tuneList&rusefi_token=xxx&vehicleName=yyy

Output:
{"tuneList": {
"id1": {"mid":"id1","name":"xxx","make":"xxx","code":"xxx","numCylinders":"x","displacement":"x","compression":"x","uploadDate":"x"},
"id2": {"mid":"id2","name":"xxx","make":"xxx","code":"xxx","numCylinders":"x","displacement":"x","compression":"x","uploadDate":"x"},
}}