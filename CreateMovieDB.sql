SET default_storage_engine=InnoDB;
DROP DATABASE IF EXISTS movieDB;
CREATE DATABASE movieDB;
USE movieDB;

CREATE TABLE `movies` (
   movieID INT not null auto_increment primary key,
   title VARCHAR(255),
   release_date DATE,
   rating DECIMAL (3,1)
);

CREATE TABLE `cast` (
   castID INT not null auto_increment primary key,
   firstName VARCHAR(100),
   lastName VARCHAR(100)
);
CREATE TABLE `castMovieRelation` (
   relationID INT not null auto_increment primary key,
   castID INT not null,
   movieID INT not null,
   relationToMovie VARCHAR(100),
   FOREIGN KEY (castID) REFERENCES `cast`(castID),
   FOREIGN KEY (movieID) REFERENCES `movies`(movieID)
);

CREATE TABLE `genre` (
   genreID INT not null auto_increment primary key,
   genreName VARCHAR(100)
);


CREATE TABLE `movieGenreRelation` (
   relationID INT not null auto_increment primary key,
   movieID INT not null,
   genreID INT not null,
   FOREIGN KEY (movieID) REFERENCES `movies`(movieID),
   FOREIGN KEY (genreID) REFERENCES `genre`(genreID)
);

INSERT INTO `genre` (genreID, genreName) VALUES (NULL, 'Action'), (NULL, 'Comedy'), (NULL, 'Drama'), (NULL, 'Horror'), (NULL, 'Romance');
INSERT INTO `movies` (movieID, title, release_date, rating) VALUES
   (NULL, 'Inception', '2010-07-16', '3.1'),
   (NULL, 'The Dark Knight', '2008-07-18', '4.1'),
   (NULL, 'Interstellar', '2014-11-07', '5.0'),
   (NULL, 'Pulp Fiction', '1994-10-14', '2.5'),
   (NULL, 'Fight Club', '1999-10-15', '1.4'),
   (NULL, 'The Matrix', '1999-03-31', '0.4'),
   (NULL, 'Forrest Gump', '1994-07-06', '0.7'),
   (NULL, 'The Godfather', '1972-03-24', '7.4'),
   (NULL, 'The Shawshank Redemption', '1994-09-23', '8.0'),
   (NULL, 'Titanic', '1997-12-19', '1.0');

-- Cast data based on IMDb credits for the above movies
INSERT INTO `cast` (castID, firstName, lastName) VALUES
   (NULL, 'Leonardo', 'DiCaprio'),
   (NULL, 'Christian', 'Bale'),
   (NULL, 'Matthew', 'McConaughey'),
   (NULL, 'Joseph', 'Gordon-Levitt'),
   (NULL, 'Heath', 'Ledger'),
   (NULL, 'Anne', 'Hathaway'),
   (NULL, 'John', 'Travolta'),
   (NULL, 'Samuel L.', 'Jackson'),
   (NULL, 'Uma', 'Thurman'),
   (NULL, 'Brad', 'Pitt'),
   (NULL, 'Edward', 'Norton'),
   (NULL, 'Helena', 'Bonham Carter'),
   (NULL, 'Keanu', 'Reeves'),
   (NULL, 'Laurence', 'Fishburne'),
   (NULL, 'Carrie-Anne', 'Moss'),
   (NULL, 'Tom', 'Hanks'),
   (NULL, 'Robin', 'Wright'),
   (NULL, 'Marlon', 'Brando'),
   (NULL, 'Al', 'Pacino'),
   (NULL, 'Morgan', 'Freeman'),
   (NULL, 'Kate', 'Winslet'),
   (NULL, 'Christopher', 'Nolan'),
   (NULL, 'Quentin', 'Tarantino'),
   (NULL, 'David', 'Fincher'),
   (NULL, 'Lana', 'Wachowski'),
   (NULL, 'Lilly', 'Wachowski'),
   (NULL, 'Robert', 'Zemeckis'),
   (NULL, 'Francis Ford', 'Coppola'),
   (NULL, 'Frank', 'Darabont'),
   (NULL, 'James', 'Cameron');

INSERT INTO `castMovieRelation` (relationID, castID, movieID, relationToMovie) VALUES
   (NULL, 1, 1, 'Actor'),
   (NULL, 4, 1, 'Actor'),
   (NULL, 22, 1, 'Director'),
   (NULL, 2, 2, 'Actor'),
   (NULL, 5, 2, 'Actor'),
   (NULL, 6, 2, 'Actor'),
   (NULL, 22, 2, 'Director'),
   (NULL, 3, 3, 'Actor'),
   (NULL, 22, 3, 'Director'),
   (NULL, 7, 4, 'Actor'),
   (NULL, 8, 4, 'Actor'),
   (NULL, 9, 4, 'Actor'),
   (NULL, 23, 4, 'Director'),
   (NULL, 10, 5, 'Actor'),
   (NULL, 11, 5, 'Actor'),
   (NULL, 12, 5, 'Actor'),
   (NULL, 24, 5, 'Director'),
   (NULL, 13, 6, 'Actor'),
   (NULL, 14, 6, 'Actor'),
   (NULL, 15, 6, 'Actor'),
   (NULL, 25, 6, 'Director'),
   (NULL, 26, 6, 'Director'),
   (NULL, 16, 7, 'Actor'),
   (NULL, 17, 7, 'Actor'),
   (NULL, 27, 7, 'Director'),
   (NULL, 18, 8, 'Actor'),
   (NULL, 19, 8, 'Actor'),
   (NULL, 28, 8, 'Director'),
   (NULL, 16, 9, 'Actor'),
   (NULL, 20, 9, 'Actor'),
   (NULL, 29, 9, 'Director'),
   (NULL, 1, 10, 'Actor'),
   (NULL, 21, 10, 'Actor'),
   (NULL, 30, 10, 'Director');

INSERT INTO `movieGenreRelation` (relationID, movieID, genreID) VALUES
   (NULL, 1, 1),
   (NULL, 2, 1),
   (NULL, 3, 1),
   (NULL, 4, 3),
   (NULL, 5, 3),
   (NULL, 6, 1),
   (NULL, 7, 3),
   (NULL, 8, 3),
   (NULL, 9, 3),
   (NULL, 10, 5);

-- ===== Cinema + gamification =====
CREATE TABLE `users` (
   userID INT not null auto_increment primary key,
   username VARCHAR(20) not null UNIQUE,
   email VARCHAR(255) not null UNIQUE,
   password_hash VARCHAR(255) not null,
   role ENUM('user','admin') not null default 'user',
);

CREATE TABLE `theaters` (
   theaterID INT not null auto_increment primary key,
   name VARCHAR(100) not null,
   seat_rows INT not null,
   seats_per_row INT not null
);

CREATE TABLE `showtimes` (
   showtimeID INT not null auto_increment primary key,
   movieID INT not null,
   theaterID INT not null,
   starts_at DATETIME not null,
   price INT not null,
   FOREIGN KEY (movieID) REFERENCES `movies`(movieID),
   FOREIGN KEY (theaterID) REFERENCES `theaters`(theaterID)
);

CREATE TABLE `bookings` (
   bookingID INT not null auto_increment primary key,
   userID INT not null,
   showtimeID INT not null,
   seat_row INT not null,
   seat_number INT not null,
   price_paid INT not null,
   created_at TIMESTAMP not null default CURRENT_TIMESTAMP,
   UNIQUE KEY one_seat_per_showing (showtimeID, seat_row, seat_number),
   FOREIGN KEY (userID) REFERENCES `users`(userID),
   FOREIGN KEY (showtimeID) REFERENCES `showtimes`(showtimeID)
);

INSERT INTO `theaters` (name, seat_rows, seats_per_row) VALUES
   ('The Grandiose Cinema', 6, 10),
   ('The Medium One', 5, 8),
   ('The Little Fella', 4, 8);

-- Each movie plays in two theaters, 3 days ahead, 2 slots per day
INSERT INTO `showtimes` (movieID, theaterID, starts_at, price)
SELECT m.movieID, t.theaterID, TIMESTAMP(DATE_ADD(CURDATE(), INTERVAL d.n DAY), s.t), 90
FROM `movies` m
JOIN `theaters` t ON t.theaterID = (m.movieID % 3) + 1 OR t.theaterID = ((m.movieID + 1) % 3) + 1
CROSS JOIN (SELECT 1 AS n UNION SELECT 2 UNION SELECT 3) d
CROSS JOIN (SELECT '18:00:00' AS t UNION SELECT '21:00:00') s;
