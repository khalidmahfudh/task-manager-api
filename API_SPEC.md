# Task Management API (Backend Only)

This document provides the API specification for the Task Management backend application, built with CodeIgniter 4.
It handles user authentication (registration, login, logout) and CRUD operations for tasks, with role-based authorization (User and Admin).

---

## 0. Common Error Responses

These error structures are used across multiple endpoints for consistent error handling.

### 0.1 Validation Error (HTTP `400 Bad Request`)

This response is returned when input data fails validation rules.

```json
{
    "status": 400,
    "error": true,
    "message": "Validation failed. Please check your input.",
    "errors": {
        "field_name_1": "Specific error message for field 1.",
        "field_name_2": "Specific error message for field 2."
    }
}
```

### 0.2 Unauthorized Error (HTTP `401 Unauthorized`)

This response is returned when the request lacks valid authentication credentials (e.g., missing or invalid access token).

```json
{
    "status": 401,
    "error": true,
    "message": "Access token is missing or invalid."
}
```

### 0.3 Forbidden Error (HTTP `403 Forbidden`)

This response is returned when the authenticated user does not have the necessary permissions to perform the action or access the resource.

```json
{
    "status": 403,
    "error": true,
    "message": "Access denied. You do not have permission to perform this action."
}
```

### 0.4 Not Found Error (HTTP `404 Not Found`)

This response is returned when the requested resource could not be found.

```json
{
    "status": 404,
    "error": true,
    "message": "Resource not found."
}
```

### 0.5 Conflict Error (HTTP `409 Conflict`)

This response is returned when a request cannot be completed due to a conflict with the current state of the target resource (e.g., trying to create a resource that already exists).

```json
{
    "status": 409,
    "error": true,
    "message": "A resource with this identifier already exists."
}
```

---

## 1. Authentication Endpoints

### 1.1 User Registration
- **URL:** `/api/register`
- **Method:** `POST`
- **Description:** Registers a new user.
- **Request Body (JSON):**
```json
{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "secretpassword",
    "password_confirmation": "secretpassword"
}
```
- **Response (Success - 201 Created):**
```json
{
    "status": 201,
    "error": false,
    "message": "User registered successfully",
    "data": {
        "id": 1, 
        "name": "John Doe",
        "email": "john.doe@example.com", 
        "role": "user"
    }
}
```
- **Response (Error - 400 Bad Request): (If registration input validation fails) Refer to [0.1 Validation Error](#01-validation-error-http-400-bad-request)**

- **Response (Error - 409 Conflict): Refer to [0.5 Conflict Error](#05-conflict-error-http-409-conflict)**


### 1.2 User Login
- **URL:** `/api/login`
- **Method:** `POST`
- **Description:** Authenticates a user and returns an access token.
- **Request Body (JSON):**
```json
{
  "email": "john.doe@example.com",
  "password": "secretpassword"
}
```
- **Response (Success - 200 OK):**
```json
{
    "status": 200,
    "error": false,
    "message": "Login successful",
    "data": {
        "user": { 
            "id": 1,
            "name": "John Doe",
            "email": "john.doe@example.com",
            "role": "user"
        },
        "token": "your_generated_jwt_token_here" 
    }
}
```
- **Response (Error - 401 Unauthorized): Refer to [0.2 Unauthorized Error](#02-unauthorized-error-http-401-unauthorized)**
- **Response (Error - 400 Bad Request): (If login input validation fails) Refer to [0.1 Validation Error](#01-validation-error-http-400-bad-request)**

### 1.3 User Logout
- **URL:** `/api/logout`
- **Method:** `POST`
- **Description:** Invalidates the current user's access token.
- **Headers:** `Authorization: Bearer <access_token>`
- **Response (Success - 200 OK):**
```json
{
    "status": 200,
    "error": false,
    "message": "Logged out successfully"
}
```
- **Response (Error - 401 Unauthorized): Refer to [0.2 Unauthorized Error](#02-unauthorized-error-http-401-unauthorized)**

---

## 2. Task Management Endpoints (User Role)

These endpoints require authentication (`Authorization: Bearer <access_token>`). Users can only manage their own tasks.

### 2.1 Get All User Tasks
- **URL:** `/api/tasks`
- **Method:** `GET`
- **Headers:** `Authorization: Bearer <access_token>`
- **Description:** Retrieves all tasks belonging to the authenticated user.
- **Response (Success - 200 OK):**
```json
{
    "status": 200,
    "error": false,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "title": "Learn CodeIgniter 4 API",
            "description": "Master CI4 for backend development.",
            "is_completed": false,
            "created_at": "2025-06-14 10:00:00",
            "updated_at": "2025-06-14 10:00:00"
        },
        {
            "id": 2,
            "user_id": 1,
            "title": "Build Task API",
            "description": null,
            "is_completed": false,
            "created_at": "2025-06-14 11:00:00",
            "updated_at": "2025-06-14 11:00:00"
        }
    ]
}
```
- **Response (Error - 401 Unauthorized): Refer to [0.2 Unauthorized Error](#02-unauthorized-error-http-401-unauthorized)**

### 2.2 Create New Task
- **URL:** `/api/tasks`
- **Method:** `POST`
- **Headers:** `Authorization: Bearer <access_token>`
- **Description:** Creates a new task for the authenticated user.
- **Request Body (JSON):**
```json
{
  "title": "Plan demo for interview",
  "description": "Outline key features to showcase."
}
```
- **Response (Success - 201 Created):**
```json
{
    "status": 201,
    "error": false,
    "message": "Task created successfully",
    "data": {
        "id": 3,
        "user_id": 1,
        "title": "Plan demo for interview",
        "description": "Outline key features to showcase.",
        "is_completed": false,
        "created_at": "2025-06-14 12:00:00",
        "updated_at": "2025-06-14 12:00:00"
    }
}
```
- **Response (Error - 400 Bad Request): (Validation errors, e.g., `title` is required) Refer to [0.1 Validation Error](#01-validation-error-http-400-bad-request)**
- **Response (Error - 401 Unauthorized): Refer to [0.2 Unauthorized Error](#02-unauthorized-error-http-401-unauthorized)**

### 2.3 Get Task Details
- **URL:** `/api/tasks/{id}`
- **Method:** `GET`
- **Headers:** `Authorization: Bearer <access_token>`
- **Description:** Retrieves details of a specific task belonging to the authenticated user by its ID.
- **Response (Success - 200 OK):**
```json
{
    "status": 200,
    "error": false,
    "data": {
        "id": 1,
        "user_id": 1,
        "title": "Learn CodeIgniter 4 API",
        "description": "Master CI4 for backend development.",
        "is_completed": false,
        "created_at": "2025-06-14 10:00:00",
        "updated_at": "2025-06-14 10:00:00"
    }
}
```
- **Response (Error - 404 Not Found): Refer to [0.4 Not Found Error](#04-not-found-error-http-404-not-found)**
- **Response (Error - 403 Forbidden): (If user tries to access a task belonging to another user) Refer to [0.3 Forbidden Error](#03-forbidden-error-http-403-forbidden)**
- **Response (Error - 401 Unauthorized): Refer to [0.2 Unauthorized Error](#02-unauthorized-error-http-401-unauthorized)**

### 2.4 Update Task
- **URL:** `/api/tasks/{id}`
- **Method:** `PUT` (or `POST` with `_method=PUT` in form-data for testing tools that don't support `PUT` directly)
- **Headers:** `Authorization: Bearer <access_token>`
- **Description:** Updates an existing task belonging to the authenticated user by its ID.
- **Request Body (JSON):**
```json
{
  "title": "Learn CodeIgniter 4 API (Updated)",
  "description": "Master CI4 for backend development and build a robust API.",
  "is_completed": true
}
```
- **Response (Success - 200 OK):**
```json
{
    "status": 200,
    "error": false,
    "message": "Task updated successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "title": "Learn CodeIgniter 4 API (Updated)",
        "description": "Master CI4 for backend development and build a robust API.",
        "is_completed": true,
        "created_at": "2025-06-14 10:00:00",
        "updated_at": "2025-06-14 13:00:00"
    }
}
```
- **Response (Error - 400 Bad Request): Refer to [0.1 Validation Error](#01-validation-error-http-400-bad-request)**
- **Response (Error - 404 Not Found): Refer to [0.4 Not Found Error](#04-not-found-error-http-404-not-found)**
- **Response (Error - 403 Forbidden): (If user tries to access a task belonging to another user) Refer to [0.3 Forbidden Error](#03-forbidden-error-http-403-forbidden)**
- **Response (Error - 401 Unauthorized): Refer to [0.2 Unauthorized Error](#02-unauthorized-error-http-401-unauthorized)**

### 2.5 Delete Task
- **URL:** `/api/tasks/{id}`
- **Method:** `DELETE`
- **Headers:** `Authorization: Bearer <access_token>`
- **Description:** Deletes a specific task belonging to the authenticated user by its ID.
- **Response (Success - 200 OK):**
```json
{
    "status": 200,
    "error": false,
    "message": "Task deleted successfully"
}
```
- **Response (Error - 404 Not Found): Refer to [0.4 Not Found Error](#04-not-found-error-http-404-not-found)**
- **Response (Error - 403 Forbidden): (If user tries to access a task belonging to another user) Refer to [0.3 Forbidden Error](#03-forbidden-error-http-403-forbidden)**
- **Response (Error - 401 Unauthorized): Refer to [0.2 Unauthorized Error](#02-unauthorized-error-http-401-unauthorized)**

---

## 3. Admin Task Management Endpoints (Admin Role)

These endpoints require authentication (`Authorization: Bearer <access_token>`) AND the authenticated user must have `role: 'admin'`.

### 3.1 Get All Tasks (Admin Only)
- **URL:** `/api/admin/tasks`
- **Method:** `GET`
- **Headers:** `Authorization: Bearer <access_token>`
- **Description:** Retrieves all tasks from all users in the system (Admin only).
- **Response (Success - 200 OK):**
```json
{
    "status": 200,
    "error": false,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "title": "Learn CodeIgniter 4 API",
            "description": "Master CI4 for backend development.",
            "is_completed": false,
            "created_at": "2025-06-14 10:00:00",
            "updated_at": "2025-06-14 10:00:00"
        },
        {
            "id": 4,
            "user_id": 2,
            "title": "Review marketing plan",
            "description": null,
            "is_completed": false,
            "created_at": "2025-06-14 14:00:00",
            "updated_at": "2025-06-14 14:00:00"
        }
        // ... more tasks
    ]
}
```
- **Response (Error - 403 Forbidden): Refer to [0.3 Forbidden Error](#03-forbidden-error-http-403-forbidden)**
- **Response (Error - 401 Unauthorized): Refer to [0.2 Unauthorized Error](#02-unauthorized-error-http-401-unauthorized)**

### 3.2 Update Any Task (Admin Only)
- **URL:** `/api/admin/tasks/{id}`
- **Method:** `PUT`
- **Headers:** `Authorization: Bearer <access_token>`
- **Description:** Updates any task in the system by its ID (Admin only).
- **Request Body (JSON):**
```json
{
  "title": "Learn CodeIgniter 4 API (Updated)",
  "description": "Master CI4 for backend development and build a robust API.",
  "is_completed": true
}
```
- **Response (Success - 200 OK):**
```json
{
    "status": 200,
    "error": false,
    "message": "Task updated successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "title": "Learn CodeIgniter 4 API (Updated)",
        "description": "Master CI4 for backend development and build a robust API.",
        "is_completed": true,
        "created_at": "2025-06-14 10:00:00",
        "updated_at": "2025-06-14 13:00:00"
    }
}
```
- **Response (Error - 400 Bad Request): Refer to [0.1 Validation Error](#01-validation-error-http-400-bad-request)**
- **Response (Error - 404 Not Found): Refer to [0.4 Not Found Error](#04-not-found-error-http-404-not-found)**
- **Response (Error - 403 Forbidden): Refer to [0.3 Forbidden Error](#03-forbidden-error-http-403-forbidden)**
- **Response (Error - 401 Unauthorized): Refer to [0.2 Unauthorized Error](#02-unauthorized-error-http-401-unauthorized)**

### 3.3 Delete Any Task (Admin Only)
- **URL:** `/api/admin/tasks/{id}`
- **Method:** `DELETE`
- **Headers:** `Authorization: Bearer <access_token>`
- **Description:**  Deletes any task in the system by its ID (Admin only).
- **Response (Success - 200 OK):**
```json
{
    "status": 200,
    "error": false,
    "message": "Task deleted successfully"
}
```
- **Response (Error - 404 Not Found): Refer to [0.4 Not Found Error](#04-not-found-error-http-404-not-found)**
- **Response (Error - 403 Forbidden): Refer to [0.3 Forbidden Error](#03-forbidden-error-http-403-forbidden)**
- **Response (Error - 401 Unauthorized): Refer to [0.2 Unauthorized Error](#02-unauthorized-error-http-401-unauthorized)**
