#!/bin/bash
# Start the Revolut Virtual POS Demo server
# Usage: ./start.sh [port]

PORT=${1:-8080}

echo "============================================"
echo " Revolut Virtual POS Demo"
echo " Starting PHP built-in server on port $PORT"
echo " URL: http://localhost:$PORT"
echo "============================================"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

php -S "localhost:$PORT" router.php
