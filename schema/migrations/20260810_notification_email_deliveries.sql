CREATE TABLE notification_deliveries (
  delivery_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  notification_id BIGINT UNSIGNED DEFAULT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  channel VARCHAR(20) NOT NULL,
  status VARCHAR(20) NOT NULL,
  sent_at DATETIME(6) DEFAULT NULL,
  error_message VARCHAR(1000) DEFAULT NULL,
  created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (delivery_id),
  UNIQUE KEY uq_notification_delivery_channel (notification_id, channel),
  KEY idx_notification_deliveries_user_status (user_id, status),
  CONSTRAINT fk_notification_delivery_notification
    FOREIGN KEY (notification_id) REFERENCES notifications (notification_id) ON DELETE SET NULL,
  CONSTRAINT fk_notification_delivery_user
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
