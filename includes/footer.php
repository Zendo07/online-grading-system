<?php

?>

<div class="footer">
    <p>&copy; 2025 Pampanga State University. All rights reserved.</p>
    <a href="#">FAQs</a>
</div>
  
<style>
/* Prevent horizontal overflow on all pages */
html, body {
  overflow-x: hidden;
  max-width: 100vw;
}

.footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  background-color: #7b2d26;
  color: #ffffff;
  font-size: 14px;
  margin-top: auto;
  box-sizing: border-box;
  flex-shrink: 0;
  width: 100%;
}

.footer p {
  color: #ffffff;
  margin: 0;
}

.footer a {
  color: #ffffff;
  text-decoration: none;
  transition: color 0.2s ease-in-out;
}

.footer a:hover {
  color: #f5a623;
  text-decoration: underline;
}

/* Smaller mobile devices */
@media (max-width: 768px) {
  .footer {
    flex-direction: column;
    gap: 8px;
    text-align: center;
    padding: 16px 20px;
    font-size: 12px;
  }
}

@media (max-width: 480px) {
  .footer {
    padding: 12px 16px;
    font-size: 11px;
  }
}
</style>