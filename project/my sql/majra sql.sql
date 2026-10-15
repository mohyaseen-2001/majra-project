CREATE TABLE `users` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255),
  `email` varchar(255) UNIQUE,
  `password` varchar(255),
  `role` enum,
  `reliability_points` int,
  `is_banned` boolean
);

CREATE TABLE `reports` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int,
  `lat` decimal,
  `lng` decimal,
  `image_path` varchar(255),
  `hazard_type` varchar(255),
  `ai_verified` boolean,
  `ai_danger_level` enum,
  `ai_reason` text,
  `status` enum,
  `admin_notes` text
);

CREATE TABLE `weather_stations` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `station_name` varchar(255),
  `lat` decimal,
  `lng` decimal,
  `current_rain_mm` decimal,
  `risk_status` enum,
  `hourly_trend` varchar(255)
);

CREATE TABLE `road_closures` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `road_name` varchar(255),
  `lat` decimal,
  `lng` decimal,
  `detour_instructions` text,
  `is_closed` boolean
);

CREATE TABLE `contacts` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255),
  `email` varchar(255),
  `subject` varchar(255),
  `message` text
);

ALTER TABLE `reports` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
