# API Documentation

## Base URL
The base URL for all API endpoints is: 
```
https://api.example.com/v1
```

## Authentication
All API requests must include an `Authorization` header with a valid token.
```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

## Endpoints

### 1. Get User Information
- **Endpoint:** `/users/{id}`  
- **Method:** `GET`  
- **Request Headers:**  
  - `Authorization: Bearer YOUR_ACCESS_TOKEN`  
- **Response:**  
  ```json
  {
      "id": 1,
      "name": "John Doe",
      "email": "john.doe@example.com"
  }
  ```  

### 2. Update User Information
- **Endpoint:** `/users/{id}`  
- **Method:** `PUT`  
- **Request Headers:**  
  - `Authorization: Bearer YOUR_ACCESS_TOKEN`  
- **Request Body:**  
  ```json
  {
      "name": "Jane Doe",
      "email": "jane.doe@example.com"
  }
  ```  
- **Response:**  
  ```json
  {
      "message": "User updated successfully"
  }
  ```  

### 3. Delete User
- **Endpoint:** `/users/{id}`  
- **Method:** `DELETE`  
- **Request Headers:**  
  - `Authorization: Bearer YOUR_ACCESS_TOKEN`  
- **Response:**  
  ```json
  {
      "message": "User deleted successfully"
  }
  ```  

## Error Handling
The API returns standard error response codes depending on the error:  
- `401 Unauthorized`: Missing or invalid authentication token.  
- `404 Not Found`: Resource not found.  
- `500 Internal Server Error`: An error occurred on the server.

## Conclusion
This document provides a brief overview of the API endpoints, authentication details, and error handling procedures. For more information, please refer to the complete API documentation.