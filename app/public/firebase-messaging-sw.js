importScripts(
  "https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js"
);
importScripts(
  "https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js"
);
// // Initialize the Firebase app in the service worker by passing the generated config
const firebaseConfig = {
  apiKey: "AIzaSyBv1zqqQP0lF6Kb24UXQRsfCsEWzBG-H7U",
  authDomain: "waimai-b3601.firebaseapp.com",
  projectId: "waimai-b3601",
  storageBucket: "waimai-b3601.firebasestorage.app",
  messagingSenderId: "1011206251691",
  appId: "1:1011206251691:web:066042a4f306c159ad03ee",
  measurementId: "G-48FTDVWCVC"
};

firebase?.initializeApp(firebaseConfig);

// Retrieve firebase messaging
const messaging = firebase?.messaging();

messaging.onBackgroundMessage(function (payload) {
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});
