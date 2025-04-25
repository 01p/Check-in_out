# Checkin App

![Checkin App](meta.png)

**Checkin App** is an open-source, Svelte-based application designed for interactive question sharing and collaboration. It supports three distinct modes: **Normal Mode**, **Presenter Mode**, and **Listener Mode**, making it ideal for team check-ins, workshops, or personal reflection.

---

## Features

- **Interactive Modes**:
  - **Normal Mode**: Shuffle and explore questions locally.
  - **Presenter Mode**: Share a unique link to control questions for others.
  - **Listener Mode**: View questions controlled by a presenter in real-time.
- **Dynamic Question Sets**:
  - Easily switch between predefined question sets (e.g., "in" and "out").
  - Supports multiple languages for questions.
- **Responsive Design**:
  - Optimized for both desktop and mobile devices.
- **Lightweight Backend**:
  - A simple PHP backend for syncing questions across users.
- **Customizable**:
  - Easily update questions via JSON files.

---

## Demo

Check out the live demo: [checkdesk.net](#)

---

## Modes Overview

### 1. **Normal Mode**
- **Description**: The default mode when the app is opened without a hash in the URL.
- **Features**:
  - Shuffle questions locally.
  - Switch between question sets (e.g., "in" and "out").
  - Select a language for the questions.
  - Enter **Presenter Mode** by generating a shareable link.

### 2. **Presenter Mode**
- **Description**: Activated when a user generates a shareable link. The presenter controls the question being displayed to all connected listeners.
- **Features**:
  - A unique hash is generated and added to the URL.
  - Shuffle questions, and the changes are synced with all listeners.
  - Share the link with others to allow them to join as listeners.

### 3. **Listener Mode**
- **Description**: Activated when a user opens the app with a hash in the URL (e.g., `http://localhost:8080?abc123`). The listener views the question controlled by the presenter.
- **Features**:
  - Automatically fetches updates from the backend every 3 seconds.
  - Listeners cannot shuffle questions or change settings.
  - Only the "Info" and "Toggle Fullscreen" buttons are visible.
  - Listeners can leave **Listener Mode** and return to **Normal Mode** via the Info modal.

---

## How to Use

### 1. **Install Dependencies**
Clone the repository and install the required dependencies:
```bash
git clone https://github.com/01p/Check-in_out.git
cd checkin-app
npm install
```

### 2. **Start the Development Server**
Start the development server:
```bash
npm run dev
```

Navigate to [localhost:8080](http://localhost:8080). You should see your app running. Edit a component file in `src`, save it, and reload the page to see your changes.

---

## Building and Running in Production Mode

To create an optimized version of the app:

```bash
npm run build
```

You can run the newly built app with:

```bash
npm run start
```
License
This project is licensed under the MIT License.

