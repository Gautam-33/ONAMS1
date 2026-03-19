# API Documentation

## 1. Get Doctors

**Endpoint:** `/api/get-doctors.php`

**Method:** GET

**Parameters:**
- None required

**Response:**
```json
{
  "success": true,
  "doctors": [
    {
      "DID": 1,
      "name": "Dr. Name",
      "specialisation": "Cardiology",
      "experience": "15",
      "contact": "9841234567",
      "photo_path": "assets/images/doctor.jpg"
    }
  ]
}
```

---

## 2. Check Availability

**Endpoint:** `/api/check-availability.php`

**Method:** POST

**Parameters:**
- `doctor_id` (required): Doctor ID
- `date` (required): Date in Y-m-d format

**Response:**
```json
{
  "success": true,
  "available": true,
  "slots": [
    {"time": "09:00:00", "available": true},
    {"time": "09:20:00", "available": false}
  ]
}
```

---

## 3. Get Recommendations (via Algorithm)

**Endpoint:** `/algorithm.php?get_recommendations=true&problem=heart+pain`

**Method:** GET

**Parameters:**
- `problem` (required): Patient problem description

**Response:**
```json
{
  "success": true,
  "count": 5,
  "recommendations": [
    {
      "doctor": {...},
      "similarity": 0.85,
      "match_percentage": 85,
      "reason": "Specializes in Cardiology. 15 years experience."
    }
  ]
}
```

---

## Error Responses

All APIs return errors in this format:

```json
{
  "success": false,
  "error": "Error message here"
}
```

---

## Status Codes

- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `500` - Server Error