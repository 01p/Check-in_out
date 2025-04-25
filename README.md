# Checkin App

This is a Svelte-based app designed for interactive question sharing and collaboration. The app supports three distinct modes: **Normal Mode**, **Presenter Mode**, and **Listener Mode**.

---

## Modes Overview

### 1. **Normal Mode**
- **Description**: This is the default mode when the app is opened without a hash in the URL.
- **Features**:
  - Users can shuffle questions.
  - Users can switch between question sets (e.g., "in" and "out").
  - Users can select a language for the questions.
  - Users can enter **Presenter Mode** by generating a shareable link.

### 2. **Presenter Mode**
- **Description**: This mode is activated when a user generates a shareable link. The presenter controls the question being displayed to all connected listeners.
- **Features**:
  - A unique hash is generated and added to the URL.
  - The presenter can shuffle questions, and the changes are synced with all listeners.
  - The presenter can share the link with others to allow them to join as listeners.

### 3. **Listener Mode**
- **Description**: This mode is activated when a user opens the app with a hash in the URL (e.g., `http://localhost:8080?abc123`). The listener views the question controlled by the presenter.
- **Features**:
  - The app automatically fetches updates from the backend every 3 seconds.
  - Listeners cannot shuffle questions or change settings.
  - Only the "Info" and "Toggle Fullscreen" buttons are visible.
  - Listeners can leave **Listener Mode** and return to **Normal Mode** via the Info modal.

---

## Get Started

Install the dependencies:

```bash
cd svelte-app
npm install
```

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

---

## Single-Page App Mode

By default, the app serves static files from the `public` folder. If you're building a single-page app (SPA) with multiple routes, you can enable SPA mode by editing the `"start"` command in `package.json`:

```json
"start": "sirv public --single"
```

---

## Using TypeScript

This template includes a script to set up a TypeScript development environment. Run it immediately after cloning the template:

```bash
node scripts/setupTypeScript.js
```

---

## Deploying to the Web

### With [Vercel](https://vercel.com)

Install `vercel` if you haven't already:

```bash
npm install -g vercel
```

Deploy your app:

```bash
cd public
vercel deploy --name my-project
```

### With [Surge](https://surge.sh/)

Install `surge` if you haven't already:

```bash
npm install -g surge
```

Deploy your app:

```bash
npm run build
surge public my-project.surge.sh
```
