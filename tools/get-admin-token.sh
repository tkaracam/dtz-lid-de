#!/bin/bash
# Admin token alma scripti

echo "========================================"
echo "DTZ Admin Token Alma"
echo "========================================"
echo ""

# API endpoint
API_URL="https://dtz-lid.de/api/auth/login.php"

echo "Hedef: $API_URL"
echo ""

# Denenecek kullanıcılar
declare -a users=(
    "admin@dtz-lid.de:Admin123!"
    "hauptadmin:HauptAdmin!2026"
)

for creds in "${users[@]}"; do
    IFS=':' read -r email password <<< "$creds"
    
    echo "Deneniyor: $email"
    
    response=$(curl -s -X POST "$API_URL" \
        -H "Content-Type: application/json" \
        -d "{\"email\":\"$email\",\"password\":\"$password\"}" \
        -w "\nHTTP_CODE:%{http_code}")
    
    http_code=$(echo "$response" | grep -o "HTTP_CODE:[0-9]*" | cut -d: -f2)
    body=$(echo "$response" | sed 's/HTTP_CODE:.*//')
    
    if [ "$http_code" == "200" ]; then
        echo "✅ BAŞARILI!"
        echo ""
        echo "Yanıt:"
        echo "$body" | python3 -m json.tool 2>/dev/null || echo "$body"
        
        # Token'i çıkar
        token=$(echo "$body" | grep -o '"access_token":"[^"]*"' | cut -d'"' -f4)
        if [ ! -z "$token" ]; then
            echo ""
            echo "========================================"
            echo "TOKEN:"
            echo "$token"
            echo "========================================"
        fi
        exit 0
    else
        echo "❌ Başarısız (HTTP $http_code)"
        echo "$body" | python3 -m json.tool 2>/dev/null || echo "$body"
        echo ""
    fi
done

echo "Tüm denemeler başarısız."
exit 1
