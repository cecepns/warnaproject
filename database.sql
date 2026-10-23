-- MySQL dump 10.13  Distrib 26.7.0, for macos26.6 (arm64)
--
-- Host: localhost    Database: warnaproject_db
-- ------------------------------------------------------
-- Server version	26.7.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ 'f804a524-a28f-11f1-bd59-5e1035daa8b8:1-5445';

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','$2y$10$Ixf3nP0sFLWqajLzTB3hZ.uv12C9pSnk0hV5otQ1aN7WlIy8jKwe2','Kamala Admin','2026-10-23 15:32:42');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Kamala Weddings',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `content` mediumtext COLLATE utf8mb4_unicode_ci,
  `status` enum('published','draft') COLLATE utf8mb4_unicode_ci DEFAULT 'published',
  `views` int DEFAULT '0',
  `published_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `status` (`status`),
  KEY `category` (`category`),
  KEY `published_at` (`published_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (1,'Top 7 Tips for Flawless Golden Hour Wedding Photos in Bali','top-7-tips-golden-hour-wedding-photos-bali','Wedding Tips','Kamala Weddings','img/wedding/A1.webp','Golden hour in Bali is magical. Discover our top professional tips to make the most of that dreamy, warm sunset glow for your wedding and pre-wedding portraits.','<h2>The Magic of Bali\'s Golden Hour</h2><p>There is something undeniably enchanting about the way the tropical sun dips below the Indian Ocean horizon in Bali. As wedding photographers with over eight years of experience capturing love stories across the island, we consider golden hour—the final hour before sunset—the absolute pinnacle of outdoor portraiture.</p><blockquote><p>\"Light is the language of photography. In Bali, that language speaks in hues of molten amber, soft rose, and luminous gold.\"</p></blockquote><h3>1. Timing is Everything: Know Your Sunset Window</h3><p>Unlike temperate climates where sunset twilight lingers for hours, tropical sunsets happen rapidly. In Bali, true golden hour typically peaks between <strong>5:15 PM and 6:15 PM</strong> depending on the time of year. Always schedule your couple portraits with at least 45 minutes of buffer time before the sun touches the horizon.</p><h3>2. Choose Locations with Clear Western Horizons</h3><p>Whether you choose the dramatic limestone cliffs of Uluwatu, the pristine black sands of Canggu, or the sweeping clifftops overlooking Nusa Dua, ensure your chosen venue or beach offers an unobstructed western view.</p><h3>3. Coordinate Wardrobe for Warm Lighting</h3><p>Flowing fabrics like silk organza, chiffon, and soft tulle catch backlight exquisitely, creating an ethereal rim light around the bride and groom. Earthy tones, ivory, champagne, and blush complement the natural golden tones of the sunset landscape.</p><h3>4. Embrace Spontaneous, Candid Movement</h3><p>Rather than stiff, formal posing, golden hour calls for genuine interaction. Walk hand in hand along the shoreline, whisper something that makes your partner laugh, and allow the ocean breeze to add dynamic flow to your hair and dress.</p><p>Ready to plan your dream wedding or pre-wedding session in Bali? Contact our team at Kamala Weddings to discuss our exclusive photography packages.</p>','published',142,'2026-10-18 08:32:42','2026-10-23 15:32:42','2026-10-23 15:32:42'),(2,'How to Feel Completely Natural & Relaxed in Front of the Camera','how-to-feel-natural-relaxed-in-front-of-camera','Pre-Wedding','Melda - Kamala Weddings','img/couples/A1.webp','Not a natural model? Don\'t worry! Here is our proven guide to shedding camera anxiety and creating authentic, emotion-filled pre-wedding photographs.','<h2>Why Almost Everyone Feels Camera-Shy At First</h2><p>If you or your fiancé tense up the moment a lens points in your direction, please know: <em>you are completely normal</em>. Over 90% of the couples we photograph tell us during our first meeting, \"We are really awkward in photos!\"</p><p>Yet, when you look through our portfolio, all you see are warm, joyful, effortless smiles. How do we get there? It is not about knowing how to pose like a supermodel—it is about feeling comfortable and being yourselves.</p><blockquote><p>\"The best wedding photos don\'t look like a photoshoot. They look like two people completely immersed in their love.\"</p></blockquote><h3>1. Treat It Like a Romantic Date</h3><p>Think of your pre-wedding shoot not as an audition, but as an intimate date where someone just happens to have a camera nearby. Hold hands, tease each other, reminisce about your first date, and focus on each other rather than the photographer.</p><h3>2. Focus on Action Rather Than Poses</h3><p>Static poses can feel awkward and stiff. We guide our couples with simple, active prompts: \"Whisper your favorite secret joke in her ear,\" or \"Gently twirl her towards the waves.\" Motion brings life, natural smiles, and real emotion to every frame.</p><h3>3. Trust Your Photographers as Friends</h3><p>At Kamala Weddings, our style is friendly, calm, and patient. We don\'t rush you, and we give clear, gentle direction so you never have to wonder what to do with your hands. By minute twenty, you will forget the camera is even there!</p>','published',98,'2026-10-11 08:32:42','2026-10-23 15:32:42','2026-10-23 15:32:42'),(3,'Editorial Wedding Trends: Intimate Ceremonies & Timeless Aesthetics','editorial-wedding-trends-intimate-ceremonies-timeless-aesthetics','Inspiration','Kamala Weddings','img/wedding/A13.webp','From micro-weddings in cliffside villas to nostalgic film-inspired color grades, discover the top aesthetic directions defining modern wedding storytelling.','<h2>The Shift Towards Intentional, Timeless Celebrations</h2><p>The wedding landscape has undergone a wonderful transformation over recent seasons. Couples are increasingly trading giant, exhausting banquets for meaningful, boutique ceremonies surrounded only by their closest family and lifelong friends.</p><h3>1. Nostalgic, Editorial Color Palettes</h3><p>Say goodbye to overly saturated, unnatural filters. Today\'s brides and grooms crave true-to-life tones with rich contrast, warm highlights, and subtle film emulation. This timeless aesthetic ensures your wedding album looks as stylish thirty years from now as it does today.</p><h3>2. Flash & Direct-Light Afterparty Photography</h3><p>Inspired by vintage 90s fashion editorials and celebrity afterparties, direct on-camera flash has become the defining look for reception dance floors. It creates high-energy, documentary-style snapshots full of laughter, movement, and unfiltered joy.</p><h3>3. Story-Driven Photojournalism</h3><p>While elegant bridal portraits will always remain essential, couples now place equal value on the quiet, unscripted in-between moments: a tear falling from a parent\'s cheek, a shared glance before walking down the aisle, or the bride kicking off her heels under the dinner table.</p><p>Interested in capturing your wedding day with an artistic, editorial touch? Explore our portfolio or send us an inquiry today.</p>','published',215,'2026-10-03 08:32:42','2026-10-23 15:32:42','2026-10-23 15:32:42');
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-23 15:34:37
