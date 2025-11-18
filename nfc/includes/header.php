<?php
// includes/header.php
// Head + opening layout wrapper. Uses Tailwind CDN.
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>HRM Dashboard</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    /* small helper to match existing look */
    .glass {
      background: linear-gradient(180deg, rgba(255,255,255,0.75), rgba(255,255,255,0.65));
      backdrop-filter: blur(6px);
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-800 antialiased">
  <!-- Layout wrapper: sidebar + main content side-by-side -->
  <div class="min-h-screen flex">
    <!-- Sidebar should be included by pages (includes/sidebar.php) -->
