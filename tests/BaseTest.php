<?php
require_once 'testconfig.php';
require_once 'PHPUnit.php';
require_once 'Dataface/Table.php';
require_once 'Dataface/DB.php';

require_once 'mysql_functions.php';

if ( !function_exists('microtime_float') ){
	function microtime_float()
	{
	   list($usec, $sec) = explode(" ", microtime());
	   return ((float)$usec + (float)$sec);
	}
}
class BaseTest extends PHPUnit_TestCase {

	var $db;
	var $table1;
	var $fieldnames_control;
	var $types_control;
	
	function BaseTest( $name = 'BaseTest'){
		$this->PHPUnit_TestCase($name);
		
		startTimer();
		$this->db = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("Could not connect to db");
		endTimer("Connect to database");
	}

	function setUp(){
		// Set up the fake environment
		//if ( !defined('DATAFACE_PATH') ){
		//	$_SERVER['PHP_SELF'] = "/dataface/tests/test.php";
		//	require_once 'init.php';
		//	init(__FILE__, $_SERVER['PHP_SELF']);
		//}
	
		
		
		startTimer();
		mysql_query("DROP DATABASE IF EXISTS ".DB_NAME, $this->db);
		mysql_query("CREATE DATABASE IF NOT EXISTS ".DB_NAME, $this->db);
		mysql_select_db(DB_NAME);
		
		
		// create the table
		
		
		mysql_query("
			CREATE TABLE IF NOT EXISTS Profiles (
				id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				fname VARCHAR(32) NOT NULL,
				lname VARCHAR(64) NOT NULL,
				title VARCHAR(64) NOT NULL,
				description TEXT,
				dob DATE,
				phone1 VARCHAR(15),
				phone2 VARCHAR(15),
				fax VARCHAR(15),
				email VARCHAR(128),
				datecreated TIMESTAMP,
				lastmodified TIMESTAMP,
				favtime TIME,
				lastlogin DATETIME,
				photo LONGBLOB,
				thumbnail BLOB,
				photo_mimetype VARCHAR(64),
				tablefield TEXT,
				FULLTEXT (title,description)
				) Type=MyISAM;", $this->db) or die("Error creating table 'Profiles': ".mysql_error($this->db));
				
		mysql_query("	
			CREATE TABLE IF NOT EXISTS ProfileProperties (
				ProfileID INT(11) NOT NULL PRIMARY KEY,
				Keywords VARCHAR(128),
				Handicap INT(5)
				) Type=MyISAM;", $this->db) or die("Error creating table 'ProfileProperties':". mysql_error($this->db));
		
		
		mysql_query("
			INSERT INTO Profiles (id,fname,lname,title,description,dob,phone1,phone2,fax,email,datecreated,lastmodified,favtime,lastlogin,photo,thumbnail,photo_mimetype) VALUES
			(10,'John','Smith','Researcher','Head of the household','1978-12-27','555-555-5555','555-555-5556','555-555-5557','shannah@sfu.ca','20051222135634','20051222135634','14:56:23','2005-06-30','000010101','00010101','text/binary'),
			(11,'Johnson','Smithson','Researcher Associate','Waiter of the household','1968-02-24','555-555-5580','555-555-5581','555-555-5583','shannah2@sfu.ca','20041112135634','20051222135634','14:56:23','2005-06-30','000010101','00010101','text/binary'),
			(12,'William','Antwone','Diver','Likes to fish','1978-09-09','555-444-5555','555-443-5556','555-444-5557','wantwone@sfu.ca','20051222135635','20051222135636','14:56:00','2005-03-30','010010101','00010101','text/binary')",
			$this->db) or die("Error inserting into table Profiles': ".mysql_query($this->db));
			
		mysql_query("
			INSERT INTO ProfileProperties (ProfileID, Keywords, Handicap) VALUES
			(10, 'English Philosopher',20)", $this->db) or die("Error inserting records into table 'ProfileProperties':".mysql_error($this->db));
			
			
				
		mysql_query("
			CREATE TABLE IF NOT EXISTS Earthtones (
				value VARCHAR(32) NOT NULL,
				label VARCHAR(32) NOT NULL,
				PRIMARY KEY (value,label)
				);", $this->db) or die("Error creating table 'Earthtones': ".mysql_error($this->db));
				
		mysql_query("INSERT INTO Earthtones (value,label) VALUES
					('brown','Brown'),
					('dirt','Dirt'),
					('blonde', 'Blonde')", $this->db) or die("Error filling table Earthtones': ".mysql_error($this->db));
					
					
		mysql_query("
			CREATE TABLE IF NOT EXISTS Degrees (
				profileid INT(11) NOT NULL,
				name VARCHAR(128) NOT NULL,
				institution VARCHAR(64),
				year YEAR);", $this->db) or die("Error creating Degrees table: ". mysql_error($this->db));
		mysql_query("
			INSERT INTO Degrees (profileid, name, institution, year) VALUES
			(10, 'Master of Technology', 'Harvard', '1998'),
			(10, 'PH.D of Technology', 'Simon Fraser University', '2002'),
			(10, 'Bachelor of Science', 'UBC', '1987'),
			(12, 'Bachelor of Arts', 'Kwantlen College', '1996')", $this->db) or die("Error filling Degrees table:". mysql_error( $this->db));
			
		
		mysql_query("
			CREATE TABLE IF NOT EXISTS Appointments (
				id INT(11) AUTO_INCREMENT PRIMARY KEY,
				profileid INT(11) NOT NULL,
				position VARCHAR(64) NOT NULL,
				startdate DATE,
				enddate DATE,
				salary DECIMAL(10,2) )", $this->db) or die("Error creating Appointments table:". mysql_error($this->db));
			
		mysql_query("
			INSERT INTO Appointments (profileid, position, startdate, enddate, salary) VALUES
			(10, 'Director','1999-12-15','2001-6-12','100000.25'),
			(10, 'Teacher', '2001-6-13','2004-8-9','65000'),
			(12, 'Sessional Instructor','2002-12-12','0000-00-00','0')", $this->db) or die("Error filling Appointments: ".mysql_error($this->db) );
			
		
		mysql_query("
			CREATE TABLE IF NOT EXISTS Addresses (
				id INT(11) AUTO_INCREMENT PRIMARY KEY,
				profileid INT(11) NOT NULL,
				line1 VARCHAR(64),
				line2 VARCHAR(64),
				line3 VARCHAR(64),
				city VARCHAR(64),
				state VARCHAR(64),
				country VARCHAR(64),
				postalcode VARCHAR(16))", $this->db) or die("Error creating Addresses table: ".mysql_error($this->db));
				
		mysql_query("
			INSERT INTO Addresses (id,profileid,line1,line2,line3,city,state,country,postalcode) VALUES 
			(1,10,'555 Elm St','Box 123','','Gotham','Washington','US','90210'),
			(2,10,'123 Perl Drive', '','', 'Springfield','Wisconsin','Canada','v1v1v1')", $this->db) or die("Error filling addresses:".mysql_error($this->db));
			
			
		mysql_query("
			CREATE TABLE Courses (
				id INT(11) AUTO_INCREMENT PRIMARY KEY,
				dept VARCHAR(32) NOT NULL,
				coursenumber VARCHAR(10) NOT NULL,
				pdf_outline BLOB
				)", $this->db) or die("Error creating table Courses: ".mysql_error($this->db));
			
		mysql_query("
			CREATE TABLE Student_Courses (
				studentid INT(11) NOT NULL,
				courseid INT(11) NOT NULL,
				PRIMARY KEY(studentid, courseid))", $this->db) or die("Error creating table Student_Courses:". mysql_error($this->db));
				
			
		mysql_query("
			CREATE TABLE Test (
				id INT(11) AUTO_INCREMENT PRIMARY KEY,
				varcharfield_text VARCHAR(64),
				varcharfield_select VARCHAR(64),
				varcharfield_checkboxes VARCHAR(255),
				varcharfield_autocomplete VARCHAR(64),
				intfield_vocab_select INT(11),
				intfield_text INT(11),
				intfield_vocab_checkbox INT(11),
				tinyintfield_text TINYINT(1),
				tinyintfield_checkbox TINYINT(1),
				smallintfield SMALLINT(3),
				bigintfield BIGINT(12),
				mediumintfield MEDIUMINT(6),
				
				floatfield FLOAT(11),
				doublefield DOUBLE,
		
		
		
				timestampfield_date TIMESTAMP,
				
		
		
		
				timestampfield_text TIMESTAMP,
				datetimefield_date DATETIME,
				datetimefield_text DATETIME,
				datefield_date DATE,
				datefield_text DATE,
				timefield_date TIME,
				timefield_text TIME,
				yearfield_date YEAR,
				yearfield_text YEAR,
				charfield CHAR,
				tinyblobfield TINYBLOB,
				tinytextfield TINYTEXT,
				blobfield BLOB,
				mediumblobfield MEDIUMBLOB,
				mediumtextfield MEDIUMTEXT,
				longblobfield LONGBLOB,
				longtextfield LONGTEXT,
				enumfield ENUM('a','b','c','d','e'),
				setfield_select SET('a','b','c','d','e'),
				setfield_checkbox SET('a','b','c','d','e'),
				boolfield BOOL
				)", $this->db) or die("Error creating table Test:". mysql_error($this->db));
				
				
		mysql_query("
			CREATE TABLE People (
				PersonID INT(11) AUTO_INCREMENT PRIMARY KEY,
				Name VARCHAR(64),
				Photo VARCHAR(128),
				Photo_mimetype VARCHAR(128),
				Interests VARCHAR(128)
				)", $this->db) or die("Error creating table People:".mysql_error($this->db));
		
		
		$names = array(
			"Angelia Darla Jacobs",
			"Antoinette Terri Mccoy",
			"Ashlee Ava Hull",
			"Barbara Acevedo",
			"Bianca Beck",
			"Callie Nash",
			"Carla Juliette Nieves",
			"Carlene Robles",
			"Claire Noel",
			"Clara Barron",
			"Daisy Louella Moran",
			"Donna Kirsten Winters",
			"Esperanza Leblanc",
			"Etta Henry",
			"Eugenia Lucinda Soto",
			"Francine Lavonne Roach",
			"Gilda Dunlap",
			"Gladys Emily Harding",
			"Helga Ada Tran",
			"Hillary Susana Bonner",
			"Ivy Juarez",
			"Janelle Minerva Adkins",
			"Jodie Farmer",
			"Johanna Loraine Cantu",
			"Josefa Sexton",
			"Juliet Reeves",
			"Kathrine Daugherty",
			"Kathrine Shepard",
			"Kayla Gray",
			"Kelli Marcia Walsh",
			"Laurel Crystal Mayo",
			"Lelia Freeman",
			"Lessie Rosa",
			"Lizzie Fowler",
			"Lydia Marie Brock",
			"Marisa Fernandez",
			"Mitzi Eve Meadows",
			"Nichole Pittman",
			"Pansy Hensley",
			"Raquel Hamilton",
			"Roxanne Hazel Higgins",
			"Sabrina Etta Woods",
			"Samantha Duran",
			"Shanna Cook",
			"Sheri Kari Burke",
			"Sheri Mccullough",
			"Staci Julianne Blanchard",
			"Tisha Pollard",
			"Toni Tania Fletcher",
			"Tonia Cannon",
			"Adalberto Valentine Hensley",
			"Andrew Morris Baker",
			"Antwan Dudley",
			"Arlen Combs",
			"Art Bass",
			"Blaine Foley",
			"Buck Leach",
			"Carmelo Moshe Bray",
			"Claude Terrence Holman",
			"Clemente Rashad Hogan",
			"Clifford Tuan Hicks",
			"Damien Harvey Robles",
			"Danial Diego Garrison",
			"Dirk Quincy Colon",
			"Dorsey Jose Buck",
			"Edgar Mitchel Nieves",
			"Ellsworth Dave Buchanan",
			"Erasmo Keller",
			"Erwin Gino Morrow",
			"Erwin Mauro Goodman",
			"Erwin Mcmahon",
			"Ethan Simpson",
			"Felix Kip Hess",
			"Gregg Dewayne Munoz",
			"Grover Kyle Sykes",
			"Huey Mendoza",
			"Irwin Finley",
			"Junior Waylon Holden",
			"Lauren Norman Woodard",
			"Lawrence Hawkins",
			"Leonardo Bradley",
			"Lester Murphy",
			"Markus Chase",
			"Maximo Porter",
			"Micheal Van Irwin",
			"Milo Fuentes",
			"Numbers Boyer",
			"Oren Washington",
			"Parker Simpson",
			"Percy Good",
			"Robin Nickolas Blankenship",
			"Rodger Sherman Atkinson",
			"Rogelio Normand Wade",
			"Rufus Rodney Cohen",
			"Shaun Elliot Witt",
			"Terrance Foreman",
			"Tom Evan Munoz",
			"Vito Atkins",
			"Wally Ewing",
			"Wilburn Scott",
			"Alec Chang Neal",
			"Alonso Herrera",
			"Alvaro Harold Stanley",
			"Ambrose Valencia",
			"Andrea Milford Duran",
			"Anton Valenzuela",
			"Antone Bennett",
			"Benjamin Dean",
			"Bernard Mohammed Grimes",
			"Bradford Battle",
			"Bradly Mendez",
			"Brock Armand Cortez",
			"Broderick Pierce",
			"Bryan Alvaro Lott",
			"Carroll Vargas",
			"Chang Harvey Lambert",
			"Christian Elvis Solomon",
			"Cruz Austin Lang",
			"Darrick Spence",
			"Dewey Major Michael",
			"Emmitt Guzman",
			"Erich Ralph Bennett",
			"Ernie Santana",
			"Errol Merritt",
			"Francisco Kermit Moran",
			"Hank King",
			"Harris Lance Buckley",
			"Herman Gilmore",
			"Hoyt Hanson",
			"Isaias Collins",
			"Johnie Moore",
			"Joshua Connie Navarro",
			"Josue Jewell Foster",
			"Kelly Donnell Boyle",
			"Laurence Webb",
			"Les Antonia Sheppard",
			"Lyle Gregory Barber",
			"Malcolm Lyle Fry",
			"Maria Elliott",
			"Nathaniel Lyons",
			"Pedro Floyd Gray",
			"Rich Sammy Sampson",
			"Richie Kory Hubbard",
			"Ronald Holcomb",
			"Roosevelt Reyes",
			"Sherman Ezekiel Strong",
			"Stevie Pearson",
			"Teodoro Dawson",
			"Theo Norris Reynolds",
			"Trent Cherry",
			"Andy Palmer",
			"Barry Genaro Faulkner",
			"Bobbie Charles",
			"Brandon Davenport",
			"Casey Freddy Carrillo",
			"Cedric Calhoun",
			"Cornelius Kurt Mcclain",
			"Darin Carson",
			"Darrick Garcia",
			"Darryl Morton Medina",
			"Del Rodrick Sosa",
			"Domenic Mccall",
			"Eli Weiss",
			"Elliot Kirk Whitaker",
			"Emilio Harris",
			"Emilio Salazar",
			"Fausto Drew Wolf",
			"Felix Cherry",
			"Filiberto Marco Preston",
			"Gary Daniels",
			"Jasper Acosta",
			"Leigh Cortez",
			"Leigh Eaton",
			"Marcel Bush",
			"Marty Leslie Nolan",
			"Myles Lawrence Weber",
			"Omar Yates",
			"Pat Santiago Chase",
			"Porfirio Seth Massey",
			"Raleigh Lawrence Peters",
			"Randell Jarvis Mcdowell",
			"Reggie Oliver",
			"Rickey Ezekiel Kemp",
			"Robbie Micheal Ayala",
			"Rod Rudy Zimmerman",
			"Ronny Quinton Vazquez",
			"Royce Houston",
			"Ruben Delacruz",
			"Rudolph Parks",
			"Rufus Terence David",
			"Russel Von Spencer",
			"Santos Jeffery Gibson",
			"Silas Shawn Giles",
			"Stevie Harding",
			"Thanh Damon Craig",
			"Tom Frost",
			"Tracy Ingram",
			"Tyrone Bernie Duncan",
			"Val Harrison Vaughan",
			"Wilson George Haney",
			"Aimee Luna",
			"Alba Gordon",
			"Alta Newman",
			"Bethany Mccoy",
			"Candice Kendra Armstrong",
			"Carly Mcmahon",
			"Christy Vicki Merritt",
			"Corina Mara Brewer",
			"Deloris Harrington",
			"Dianne Keller",
			"Dina Hahn",
			"Dolores Harris",
			"Elinor Corine Watkins",
			"Elinor Lowery",
			"Evangeline Minnie Kemp",
			"Eve Pollard",
			"Jane Tate",
			"Janet Bryant",
			"Jessica Clemons",
			"Jill Millicent Hayes",
			"June Ora Leonard",
			"Katie Elvia Diaz",
			"Kenya Rivers",
			"Kristi Burch",
			"Kristy Althea Eaton",
			"Lana Bradshaw",
			"Liliana Gomez",
			"Lizzie Felicia Douglas",
			"Lucinda Cannon",
			"Maricela Bridges",
			"Minerva Deirdre Buckner",
			"Nettie Spence",
			"Nicole Beard",
			"Paige Baldwin",
			"Pansy Winters",
			"Penny Tamika Santos",
			"Priscilla Macias",
			"Reba Kelly",
			"Rochelle Vincent",
			"Ronda Aileen Macias",
			"Rosalie Rachel Bryan",
			"Rosanna Brady",
			"Roxanne Lucas",
			"Sheena Krystal Rowe",
			"Sheryl Dennis",
			"Tabitha Lena Calderon",
			"Tameka Mejia",
			"Tasha Buckner",
			"Tiffany Levine",
			"Vera Rosanne Joyce");
		foreach ( $names as $name){
			mysql_query("INSERT INTO People (Name,Photo) VALUES ('$name','".str_replace(' ','_',$name).".jpg')", $this->db) or die("Error inserting value '$name' into People table". mysql_error($this->db));
			
		}
		
		
		mysql_query('
			CREATE TABLE Publications (
				PublicationID INT(11) AUTO_INCREMENT PRIMARY KEY,
				PubType VARCHAR(64),
				BiblioString TEXT
				)', $this->db) or die("Error creating Publications table.".mysql_error($this->db));
				
		/*mysql_query('
			CREATE TABLE Publications_fr (
				PublicationID INT(11) AUTO_INCREMENT PRIMARY KEY,
				PubType VARCHAR(64),
				BiblioString TEXT
				)', $this->db) or die("Error creating Publications table.".mysql_error($this->db));*/
		
		$publications = array(
			"Amit, H. Autonomous, metamorphic technology for B-Trees. In POT NOSSDAV (Dec. 1991).",
			"Bhabha, T., McCarthy, J., and Shenker, S. The influence of pseudorandom information on electrical engineering. NTT Technical Review 8 (Sept. 2000), 76-85.",
			"Chomsky, N., Suzuki, Z., Shenker, S., and Bose, Q. On the deployment of the partition table. OSR 60 (Nov. 1999), 159-191.",
			"Corbato, F., Lakshminarayanan, K., and Maruyama, E. W. Constructing expert systems and 802.11b using Last. In POT the Conference on Real-Time, Scalable Information (June 2001).",
			"Garcia, S. Decoupling Boolean logic from the memory bus in Boolean logic. Journal of Concurrent Theory 388 (July 1999), 20-24.",
			"Hennessy, J., and Gayson, M. Relational, scalable methodologies for I/O automata. Journal of Relational Methodologies 50 (July 1993), 20-24.",
			"Hennessy, J., and Ritchie, D. Decoupling simulated annealing from link-level acknowledgements in courseware. In POT HPCA (May 1992).",
			"Jacobson, V., Chomsky, N., and Jones, D. Certifiable epistemologies for 2 bit architectures. In POT SIGGRAPH (Dec. 1997).",
			"Knuth, D. Controlling symmetric encryption and IPv4. Journal of Collaborative, Classical Symmetries 41 (Apr. 2005), 87-100.",
			"Kobayashi, E., and Walsh, J. Lossless, embedded theory for XML. In POT POPL (Sept. 1993).",
			"Kobayashi, R. Architecting Internet QoS using compact epistemologies. Tech. Rep. 782, MIT CSAIL, Apr. 2001.",
			"Kubiatowicz, J., and Smith, J. E-commerce considered harmful. In POT the USENIX Security Conference (July 1994).",
			"Lakshminarasimhan, K., Erd…S, P., and Simon, H. Architecture considered harmful. In POT the Workshop on Authenticated, Replicated Methodologies (Apr. 1996).",
			"Lamport, L. On the emulation of Smalltalk. Journal of Automated Reasoning 45 (Jan. 2004), 59-68.",
			"Lampson, B., and Needham, R. An evaluation of I/O automata. In POT INFOCOM (Nov. 2004).",
			"Martinez, Y. M. Developing virtual machines using reliable modalities. TOCS 8 (Apr. 1999), 1-17.",
			"Maruyama, C. T. Towards the synthesis of kernels. In POT the Workshop on Stable Symmetries (Oct. 1997).",
			"Newell, A. The relationship between randomized algorithms and fiber-optic cables with FlamyPeece. In POT the USENIX Technical Conference (Dec. 2001).",
			"Qian, N. Decoupling object-oriented languages from cache coherence in e-business. Tech. Rep. 45, Stanford University, Aug. 1997.",
			"Rangachari, J., and White, O. The effect of random communication on mutually partitioned operating systems. In POT the Symposium on Permutable, Scalable Epistemologies (May 2004).",
			"Smith, P. Scheme considered harmful. Tech. Rep. 1866-49, Microsoft Research, Sept. 2002.",
			"Ullman, J. An investigation of the UNIVAC computer. In POT IPTPS (July 1997).",
			"Walsh, J., and Takahashi, L. An investigation of DHTs using coffer. In POT SIGCOMM (Sept. 1990).",
			"Abiteboul, S., Tarjan, R., and Hoare, C. A. R. TROPE: A methodology for the improvement of randomized algorithms. IEEE JSAC 4 (June 2005), 20-24.",
			"White, V. Mobile, knowledge-based symmetries. Journal of Stable, Optimal, Introspective Symmetries 52 (Sept. 2005), 73-96.",
			"Wirth, N., Bose, K., Vijayaraghavan, R., and Cook, S. Contrasting agents and Byzantine fault tolerance. In POT SIGGRAPH (Mar. 2005).",
			"B. Batta, \"The Turing machine no longer considered harmful,\" Journal of Authenticated, Decentralized Modalities, vol. 82, pp. 45-52, Oct. 2004.",
			"E. Codd and X. Martin, \"Deconstructing write-back caches using scoop,\" in POT MOBICOM, June 2001.",
			"T. Leary, V. Jones, D. Estrin, U. Zhao, and D. Patterson, \"Superblocks no longer considered harmful,\" in POT NDSS, Apr. 2005.",
			"G. I. Kumar, \"Refining the Internet and congestion control,\" Journal of Empathic, Stable Communication, vol. 77, pp. 56-67, Aug. 2004.",
			"R. Watanabe and E. Nehru, \"Contrasting robots and multicast algorithms,\" in POT FPCA, Apr. 1993.",
			"M. Wang and M. Minsky, \"TIT: Cacheable, certifiable theory,\" Journal of Client-Server Archetypes, vol. 1, pp. 45-57, Apr. 2003.",
			"D. Knuth and M. Welsh, \"Decoupling wide-area networks from object-oriented languages in the Ethernet,\" Journal of Mobile, Interposable, Empathic Archetypes, vol. 74, pp. 51-64, Feb. 2005.",
			"J. McCarthy, \"The impact of homogeneous methodologies on robotics,\" Devry Technical Institute, Tech. Rep. 42, June 1990.",
			"I. Harris and G. Wu, \"Synthesis of Voice-over-IP,\" in POT NDSS, Sept. 1999.",
			"O. Robinson, \"A simulation of journaling file systems using simia,\" Journal of Distributed, Introspective Models, vol. 48, pp. 40-55, Dec. 2003.",
			"H. a. Martin, L. Wang, and R. T. Morrison, \"Towards the analysis of neural networks,\" Journal of Cacheable, Trainable Communication, vol. 55, pp. 86-109, Oct. 2002.",
			"F. Bose and G. Zhao, \"The influence of robust theory on e-voting technology,\" in POT the Conference on Omniscient, Electronic Modalities, Jan. 2000.",
			"R. Hamming, \"Decoupling forward-error correction from lambda calculus in object- oriented languages,\" NTT Technical Review, vol. 3, pp. 83-101, Mar. 2005.",
			"K. Lakshminarayanan, \"A case for courseware,\" in POT IPTPS, Sept. 2005.",
			"J. Smith, \"Theoretical unification of a* search and evolutionary programming,\" in POT POPL, Oct. 2004.",
			"J. Cocke, T. Robinson, and Y. Sasaki, \"Deconstructing lambda calculus,\" Journal of Extensible, Knowledge-Based Models, vol. 34, pp. 52-69, Jan. 2002.",
			"H. Garcia-Molina, \"A deployment of thin clients that would allow for further study into write- ahead logging,\" in POT SOSP, Oct. 2001.",
			"D. Estrin, S. Cook, and H. Levy, \"Decoupling Byzantine fault tolerance from digital-to-analog converters in checksums,\" in POT the Symposium on Secure, Signed Information, Dec. 2004.",
			"R. Tarjan, K. Thompson, and X. Williams, \"Deploying superblocks and vacuum tubes using Loord,\" in POT VLDB, Oct. 2005.",
			"S. Bhabha and I. Newton, \"The relationship between redundancy and agents with HUN,\" in POT the Workshop on Semantic, Virtual Algorithms, Mar. 2002.",
			"C. Moore, J. Dongarra, and W. Kahan, \"\"smart\", linear-time, pervasive configurations for systems,\" in POT SOSP, Feb. 1999.",
			"O. Z. Qian and K. Li, \"Deploying the World Wide Web using knowledge-based archetypes,\" in POT FOCS, Aug. 2005.",
			"J. Kubiatowicz, \"FICHU: Refinement of thin clients,\" Journal of Permutable, Amphibious Configurations, vol. 76, pp. 1-17, June 1992.",
			"N. Wirth, C. Harris, and J. Hartmanis, \"A methodology for the refinement of 802.11 mesh networks,\" Journal of Large-Scale Symmetries, vol. 50, pp. 20-24, May 2004.",
			"C. Leiserson, \"Probabilistic, autonomous configurations,\" in POT the Symposium on Knowledge-Based Epistemologies, Aug. 1997.",
			"E. S. Williams and H. Qian, \"Investigating wide-area networks and agents,\" Journal of Highly-Available, Interposable Methodologies, vol. 8, pp. 58-64, Nov. 2003.",
			"B. Lampson and V. Ramasubramanian, \"Miaul: A methodology for the visualization of evolutionary programming,\" Journal of Robust Communication, vol. 7, pp. 76-82, Mar. 2005.",
			"X. Jones, O. Kobayashi, H. Levy, and J. Maruyama, \"Harnessing link-level acknowledgements and RAID using Uzema,\" in POT HPCA, May 1993.",
			"Q. Wilson and P. M. Johnson, \"An improvement of spreadsheets,\" in POT HPCA, May 2005.Brown, O. Thin clients no longer considered harmful. In POT the Workshop on Amphibious, Amphibious Configurations (Dec. 1991).",
			"Codd, E., Shastri, V., Watanabe, T., Sasaki, B., and Patterson, D. The relationship between web browsers and lambda calculus. Tech. Rep. 7989-93-783, Devry Technical Institute, May 1992.",
			"Cook, S., Watanabe, H. E., Sun, E., and Hennessy, J. Encrypted symmetries for SCSI disks. In POT the Workshop on Embedded, Wireless Algorithms (Apr. 2003).",
			"Davis, N. Empathic, lossless, introspective methodologies. Journal of Ambimorphic, Efficient Modalities 6 (June 2002), 152-197.",
			"Davis, N. I., Brown, V., Wu, C., and Garey, M. Kemp: A methodology for the simulation of the lookaside buffer. Journal of Large-Scale, Empathic Archetypes 97 (July 2003), 57-66.",
			"Dijkstra, E., and Simon, H. The effect of Bayesian configurations on complexity theory. Journal of Secure, Scalable Information 5 (Apr. 1998), 74-99.",
			"Feigenbaum, E., and Rivest, R. Pervasive, constant-time, authenticated algorithms. OSR 43 (Aug. 2003), 1-17.",
			"Garcia-Molina, H., and Nels, J. Simulation of multicast methodologies. Journal of Autonomous, Robust Communication 82 (Oct. 1995), 84-104.",
			"Garcia-Molina, H., and Sato, J. A deployment of evolutionary programming using bearncops. In POT the Symposium on Heterogeneous Technology (May 1994).",
			"Hamming, R. Improving access points and multi-processors. In POT FPCA (Apr. 1999).",
			"Hawking, S. Visard: Optimal, relational communication. OSR 20 (Oct. 2004), 155-199.",
			"Ito, N. Simulating compilers using interactive technology. NTT Technical Review 0 (Jan. 1998), 1-16.",
			"Iverson, K., and Wirth, N. A case for extreme programming. Journal of Low-Energy Archetypes 45 (June 2003), 58-61.",
			"Jackson, B. Deconstructing IPv7 with BRAE. TOCS 85 (Jan. 2001), 56-69.",
			"Jones, R. Visualizing local-area networks using decentralized modalities. In POT NDSS (Sept. 2004).",
			"Perlis, A., and Pnueli, A. Constructing the producer-consumer problem using multimodal algorithms. In POT the Workshop on Data Mining and Knowledge Discovery (Dec. 2002).",
			"Suzuki, U., Ito, X., Yao, A., and Gupta, a. Deconstructing neural networks with BortTenuity. In POT the Symposium on Extensible Models (Jan. 2002).",
			"Taylor, J., Needham, R., Zheng, G., Smith, J., Shamir, A., Gupta, X., Balboa, R., and McCarthy, J. On the refinement of the memory bus. In POT the Symposium on Stochastic Archetypes (May 2004).",
			"Welsh, M., and Moore, K. Kip: Development of 802.11b. In POT the Conference on Game-Theoretic, Embedded Configurations (July 2000).",
			"White, L. A case for the World Wide Web. Journal of Automated Reasoning 43 (Jan. 1995), 44-53.",
			"Wilson, Q. Controlling web browsers and I/O automata. In POT the Conference on Low-Energy, Game-Theoretic Information (May 1991).",
			"Zheng, E. B., Shenker, S., and Yao, A. ChanukaInia: Evaluation of hierarchical databases. In POT NSDI (Nov. 2004).",
			"Backus, J. Hash tables no longer considered harmful. In POT PODS (May 2004).",
			"Cook, S., and Bhabha, Q. Reliable, heterogeneous models for reinforcement learning. Journal of Automated Reasoning 64 (Apr. 2001), 1-11.",
			"Dijkstra, E. Wye: Understanding of architecture. In POT MICRO (Jan. 2004).",
			"Karp, R., Martinez, Y., Maruyama, B., Brown, P., Stallman, R., and Hawking, S. WHELK: Development of expert systems. Journal of Automated Reasoning 92 (May 2003), 154-195.",
			"Lee, M., and Williams, W. Thin clients no longer considered harmful. Journal of Introspective, Scalable Modalities 923 (Nov. 1992), 72-96.",
			"Milner, R., and Stearns, R. A methodology for the refinement of e-business. In POT IPTPS (Mar. 2004).",
			"Moore, B. The relationship between RAID and Scheme. Journal of Cooperative, Trainable Methodologies 216 (Sept. 1993), 54-64.",
			"Nygaard, K., and Bose, G. Robots considered harmful. Journal of Cacheable Modalities 1 (Jan. 2000), 150-196.",
			"Papadimitriou, C., Stallman, R., Davis, Y., and Leary, T. Developing e-business using psychoacoustic epistemologies. Journal of Game-Theoretic Communication 17 (Oct. 1999), 85-108.",
			"Qian, L., Rivest, R., Garcia, V., and Maruyama, N. Uncial: A methodology for the emulation of the Turing machine. In POT the WWW Conference (Aug. 1995).",
			"Rajagopalan, L., Agarwal, R., and Fredrick P. Brooks, J. DHCP considered harmful. NTT Technical Review 52 (Feb. 2001), 46-55.",
			"Sutherland, I., Nygaard, K., Ullman, J., Patterson, D., Tarjan, R., Codd, E., and Anderson, Q. Scauper: A methodology for the construction of context-free grammar. Tech. Rep. 59-100, UC Berkeley, Dec. 2004.",
			"Suzuki, H., and Wirth, N. Symbiotic, \"fuzzy\" models for kernels. In POT ASPLOS (Oct. 2005).",
			"Suzuki, V., Milner, R., Tarjan, R., Floyd, S., Kahan, W., Lee, M., and Jones, X. The impact of cacheable information on networking. In POT the Conference on Linear-Time Technology (Oct. 2005).",
			"Taylor, W., Darwin, C., and Tanenbaum, A. Towards the improvement of kernels. In POT NSDI (Dec. 2003).",
			"Thomas, I., Hunt, H., Sutherland, I., Milner, R., Kobayashi, K., Taylor, Q., Bose, J., and Karp, R. A case for Voice-over-IP. In POT the Conference on Certifiable, Distributed Configurations (Jan. 2000).",
			"Watanabe, G., Daubechies, I., Zhou, I., Williams, I., and Hartmanis, J. The impact of modular models on extensible secure artificial intelligence. Journal of Pervasive, Low-Energy Modalities 750 (Apr. 2001), 158-193.",
			"Williams, O., Harris, G., Maruyama, O. X., Estrin, D., and Davis, M. Manus: Encrypted methodologies. In POT the Symposium on Real-Time Information (Jan. 2004).",
			"Wu, H., Floyd, R., Sutherland, I., Iverson, K., Thompson, D., Minsky, M., Smith, J., and Abiteboul, S. Towards the emulation of multicast heuristics. Journal of Compact, Perfect Methodologies 36 (Oct. 2005), 20-24.",
			"Zhao, L. L. Comparing the location-identity split and IPv6. In POT WMSCI (Sept. 1994).Abiteboul, S., Williams, D., and Einstein, A. TubalTrevat: A methodology for the analysis of Internet QoS. In POT NSDI (Sept. 2002).",
			"Anderson, K. A case for telephony. In POT the USENIX Security Conference (July 2005).",
			"Anderson, T. U. Contrasting forward-error correction and active networks. Journal of Extensible, Symbiotic Theory 49 (Sept. 2001), 89-104.",
			"Bhabha, H. Contrasting I/O automata and 802.11b using gree. Journal of Ubiquitous, Self-Learning, Client-Server Algorithms 49 (Jan. 2004), 82-104.",
			"Bose, G., Miller, Z., and McCarthy, J. Encrypted, introspective, introspective symmetries for evolutionary programming. In POT OSDI (Apr. 2005).",
			"Cocke, J., and Miller, S. Web services considered harmful. In POT POPL (Nov. 1993).",
			"Creed, A. On the important unification of interrupts and simulated annealing. Journal of Trainable, Bayesian Information 6 (Nov. 2000), 84-104.",
			"Dahl, O. Deconstructing a* search using PreyfulEstufa. In POT NSDI (Mar. 1967).",
			"Dijkstra, E. Bidet: Client-server, permutable theory. Journal of Wireless, Random Modalities 2 (June 1967), 79-85.",
			"Floyd, R., Moore, Q., Bhabha, E., Knuth, D., Yao, A., and Takahashi, D. On the refinement of Lamport clocks. Journal of Robust, Wearable Algorithms 334 (Nov. 2005), 73-92.",
			"Gayson, M., Minsky, M., Martin, X., Lamport, L., Watanabe, J., Chretien, J., Miller, V., and Shamir, A. A synthesis of Internet QoS. In POT SIGCOMM (June 2005).",
			"Gupta, a., and Smith, J. LivonianGaggle: Synthesis of the memory bus. In POT the Symposium on Adaptive Epistemologies (Oct. 1999).",
			"Harris, G., and Raman, C. Deconstructing consistent hashing with loutouattle. NTT Technical Review 0 (Feb. 2003), 157-195.",
			"Hartmanis, J., Rivest, R., Milner, R., Welsh, M., and Martinez, B. The relationship between the location-identity split and operating systems with RugateHurst. Journal of Signed, Empathic Algorithms 6 (July 2000), 157-198.",
			"Hopcroft, J. Towards the refinement of vacuum tubes. In POT the Symposium on Bayesian, Compact Information (Oct. 2002).",
			"Ito, N., and Stearns, R. CivicTaring: Heterogeneous information. In POT INFOCOM (Mar. 2002).",
			"Kahan, W. Redundancy considered harmful. In POT FOCS (Oct. 1996).",
			"Kubiatowicz, J. Permutable, efficient information for the lookaside buffer. Journal of Embedded Information 4 (May 2005), 20-24.",
			"Kumar, S., and Brown, X. Deconstructing kernels. Journal of Automated Reasoning 5 (May 2001), 87-101.",
			"Maruyama, O. Analyzing public-private key pairs using multimodal modalities. In POT the USENIX Security Conference (Feb. 2005).",
			"Minsky, M., Maruyama, I. T., Gayson, M., and Moore, X. A development of the World Wide Web. Tech. Rep. 95, Stanford University, Nov. 1995.",
			"Moore, T., Jacobson, V., Hoare, C., and Taylor, H. Visualizing online algorithms using metamorphic algorithms. In POT ECOOP (Feb. 2003).",
			"Ramasubramanian, V. Visualizing local-area networks and simulated annealing. Tech. Rep. 92/231, Devry Technical Institute, Mar. 1994.",
			"Robinson, C., Scott, D. S., and Lee, M. Z. An investigation of a* search with Vat. In POT SIGCOMM (June 1991).",
			"Shastri, N., and Minsky, M. Emulation of telephony. Journal of Constant-Time, Mobile Configurations 70 (Sept. 2002), 156-195.",
			"Shenker, S., Davis, F. M., Creed, A., Nygaard, K., and Wirth, N. The effect of mobile epistemologies on theory. OSR 62 (Apr. 2001), 87-103.",
			"Smith, G. The influence of concurrent information on complexity theory. In POT the Symposium on Client-Server, Heterogeneous Configurations (Mar. 2000).",
			"Smith, J. M., and Garcia-Molina, H. Studying extreme programming and the UNIVAC computer using Oul. NTT Technical Review 95 (Nov. 2002), 20-24.",
			"Smith, N., and Levy, H. Emulating semaphores using constant-time technology. In POT the Symposium on Psychoacoustic, Electronic Symmetries (July 1993).",
			"Smith, Y. F., Venugopalan, R. O., and Milner, R. Deconstructing online algorithms with Mayfish. In POT JAIR (July 1992).",
			"Taylor, M., Wilson, C., and Sutherland, I. On the synthesis of red-black trees. Journal of Certifiable, Bayesian Communication 91 (July 1999), 1-11.",
			"Turing, A., Jones, N., Welsh, M., Wirth, N., Gayson, M., Bose, R., Creed, A., Wang, N., and Karp, R. Constant-time, collaborative technology. Journal of Read-Write, Pseudorandom Technology 93 (Sept. 2002), 46-52.",
			"White, J., Ramanan, O., and Sasaki, W. The influence of wireless technology on machine learning. Tech. Rep. 678-5887-216, University of Washington, Jan. 2004.",
			"Wilkes, M. V., Bush, G., Sasaki, S., and Brown, S. On the investigation of I/O automata. In POT the USENIX Security Conference (Apr. 2000).",
			"Zhao, V., Shamir, A., and Kumar, M. Fid: A methodology for the evaluation of model checking. Journal of Omniscient, Optimal Algorithms 37 (Sept. 2003), 79-94.",
			"Abiteboul, S., Fredrick P. Brooks, J., Sasaki, Z., and Ritchie, D. Towards the analysis of the partition table. In POT MOBICOM (July 2004).",
			"Anderson, R., Gayson, M., Leonard, R., and Martinez, C. Synthesizing agents and evolutionary programming. In POT the Conference on Semantic, Symbiotic Theory (Feb. 1998).",
			"Cocke, J., and Wilkinson, J. A case for telephony. Journal of Wearable, Distributed, Amphibious Symmetries 94 (Feb. 1994), 40-56.",
			"Erd…S, P. A case for write-ahead logging. Journal of Ambimorphic, Symbiotic Methodologies 13 (Dec. 2003), 41-55.",
			"Estrin, D., and Iverson, K. Decoupling Internet QoS from symmetric encryption in reinforcement learning. OSR 87 (May 2004), 1-18.",
			"Garcia, F., and Cook, S. Concurrent, electronic epistemologies. In POT the Symposium on Robust, Distributed Methodologies (Jan. 2002).",
			"Jacobson, V., Takahashi, M., and Leonard, R. Write-ahead logging considered harmful. Journal of Interposable, \"Smart\" Symmetries 54 (Sept. 1998), 1-13.",
			"Jones, W., Bachman, C., and Gupta, a. A methodology for the significant unification of 802.11b and evolutionary programming. In POT the USENIX Security Conference (Feb. 2004).",
			"Knuth, D. OftShamoy: Construction of red-black trees. In POT the Conference on Metamorphic, Perfect Methodologies (Jan. 2003).",
			"Kobayashi, I. T. Studying object-oriented languages using wearable models. OSR 47 (Sept. 2003), 1-13.",
			"Lee, a., and Nygaard, K. The relationship between replication and the producer-consumer problem. Tech. Rep. 382-787-901, IBM Research, Nov. 2005.",
			"Leiserson, C. Deploying the partition table using ambimorphic epistemologies. In POT the Workshop on Certifiable, Extensible Configurations (Feb. 2001).",
			"Maruyama, P. S., and Reddy, R. The Ethernet considered harmful. In POT ECOOP (July 1992).",
			"Milner, R., Fredrick P. Brooks, J., Williams, E. D., and Minsky, M. Investigation of 64 bit architectures. In POT HPCA (Aug. 1993).",
			"Nygaard, K., Gayson, M., and Kumar, D. The influence of unstable methodologies on networking. In POT the Workshop on Certifiable Epistemologies (Apr. 2002).",
			"Nygaard, K., Smith, L. E., and Ramasubramanian, V. Harnessing Scheme and a* search with BrackyCider. In POT IPTPS (Dec. 1994).",
			"Perlis, A., and Milner, R. On the deployment of e-commerce. Journal of Introspective, Authenticated Configurations 64 (Oct. 1998), 59-66.",
			"Pnueli, A. Emulating the Turing machine using game-theoretic communication. Journal of Game-Theoretic, Peer-to-Peer Modalities 183 (July 2004), 49-55.",
			"Raghavan, J. F. Towards the understanding of consistent hashing. In POT the USENIX Security Conference (July 1996).",
			"Robinson, P., Kobayashi, P. G., Lampley, J., Quinlan, J., Li, K., Leonard, R., Scott, D. S., Dongarra, J., Ito, G., White, G., and Garcia, O. Developing B-Trees and systems using Parter. Journal of Automated Reasoning 3 (Feb. 2005), 48-57.",
			"Stallman, R. The influence of electronic communication on hardware and architecture. Journal of Symbiotic, Encrypted Archetypes 12 (Nov. 2005), 48-55.",
			"Suzuki, S. O., and Corbato, F. Comparing Web services and 64 bit architectures. Journal of Stable, Psychoacoustic, Signed Communication 88 (Oct. 2005), 51-69.",
			"Takahashi, K. A case for Lamport clocks. Journal of Virtual Communication 2 (May 1999), 74-94.",
			"Thomas, F. The impact of permutable algorithms on theory. Journal of Constant-Time, Mobile, Pervasive Methodologies 7 (Mar. 2005), 73-98.",
			"Wilkes, M. V. A case for model checking. Tech. Rep. 196-4039, Harvard University, Jan. 2005.",
			"Wilson, X. Towards the exploration of Boolean logic that would make constructing model checking a real possibility. In POT the USENIX Security Conference (Nov. 1990).",
			"Yao, A. The effect of efficient methodologies on complexity theory. In POT JAIR (June 2002).",
			"Zhao, E. D., Maruyama, B., Davis, J., White, W., Garey, M., Wilkinson, J., Gupta, H., Zhao, E., and Harris, S. Evaluating erasure coding and hierarchical databases with Stipel. In POT IPTPS (July 1992).",
			"Zhao, Y., Wang, P., Wilson, B., Darwin, C., Stearns, R., and Johnson, X. On the emulation of RAID. Tech. Rep. 4409/431, Intel Research, Jan. 1999.",
			"Adleman, L. Deploying multicast approaches using pseudorandom modalities. Journal of Cooperative Models 0 (Dec. 2002), 83-101.",
			"Anderson, F. AllTroll: A methodology for the evaluation of agents. In POT WMSCI (July 2002).",
			"Backus, J., Hamming, R., and Estrin, D. Emulating Smalltalk using wireless methodologies. In POT SIGCOMM (July 2005).",
			"Blum, M. Visualizing write-back caches using semantic modalities. In POT SIGCOMM (Nov. 2003).",
			"Bose, Z., and Turing, A. Contrasting reinforcement learning and extreme programming. In POT NSDI (Mar. 1991).",
			"Brown, X. AltLalo: A methodology for the deployment of redundancy. In POT the Conference on Heterogeneous, Client-Server Technology (Dec. 2004).",
			"Cocke, J., Bhabha, O., and Bachman, C. Decoupling online algorithms from superblocks in Lamport clocks. In POT POPL (July 2004).",
			"Garcia-Molina, H., Quinlan, J., Floyd, R., Shenker, S., Kobayashi, I., Hartmanis, J., Zhao, R., and Lee, L. Deconstructing consistent hashing. IEEE JSAC 4 (Aug. 1996), 43-52.",
			"Garey, M., Clark, D., Williams, J., Codd, E., Watanabe, L., and Martinez, T. Certifiable modalities. Journal of Cacheable, Encrypted Models 34 (Aug. 1990), 72-96.",
			"Jackson, D., and Codd, E. Knowledge-based theory for the transistor. In POT SIGGRAPH (May 1991).",
			"Jackson, K. Z. Constant-time, real-time archetypes for red-black trees. In POT NDSS (Jan. 2000).",
			"Martin, I., and Corbato, F. Evolutionary programming considered harmful. Tech. Rep. 6271/1126, Stanford University, July 2005.",
			"Martinez, G., and Culler, D. The effect of linear-time configurations on hardware and architecture. In POT the Conference on Omniscient, Pseudorandom, Optimal Modalities (July 2003).",
			"Maruyama, N. E., and Clark, D. The influence of distributed epistemologies on artificial intelligence. In POT the Conference on Metamorphic, Wireless Technology (Apr. 2001).",
			"Minsky, M., Wilkinson, J., and Dongarra, J. RoyShackle: Cooperative epistemologies. In POT NOSSDAV (July 2002).",
			"Newell, A., Hawking, S., and Sutherland, I. On the simulation of model checking. Journal of Pseudorandom, Constant-Time Models 52 (May 2001), 79-97.",
			"Newton, I. Deconstructing Boolean logic. In POT the Conference on Encrypted, Client-Server Epistemologies (Mar. 2005).",
			"Qian, N., Jones, Y., Hoare, C. A. R., Newton, I., Smith, T. X., and Suzuki, L. Visualizing IPv6 using interactive epistemologies. In POT NDSS (Sept. 2002).",
			"Rivest, R., Shenker, S., White, C., and Dijkstra, E. Modular, omniscient symmetries for forward-error correction. In POT the USENIX Security Conference (July 1997).",
			"Robinson, D., Lee, X., and Jackson, P. Emulating virtual machines using cacheable archetypes. In POT SIGCOMM (Mar. 2005).",
			"Robinson, R., Gupta, T., and Stearns, R. On the study of the Ethernet. Tech. Rep. 9716/23, UCSD, Dec. 1970.",
			"Schroedinger, E. A methodology for the emulation of rasterization. Tech. Rep. 15, MIT CSAIL, Aug. 1993.",
			"Scott, D. S. Random, probabilistic methodologies for kernels. Journal of Scalable Epistemologies 69 (Nov. 1999), 72-89.",
			"Shastri, a., Garcia-Molina, H., Zheng, T. L., Tyson, M., and Wu, T. On the investigation of Boolean logic. In POT the Workshop on Amphibious, Highly-Available, Wireless Communication (Sept. 1990).",
			"Sun, D. EDITOR: A methodology for the intuitive unification of wide-area networks and evolutionary programming. In POT the Conference on Self-Learning Epistemologies (Aug. 1993).",
			"Sun, L., Hopcroft, J., Reddy, R., and Walters, B. Extreme programming considered harmful. In POT the Workshop on Data Mining and Knowledge Discovery (Aug. 2002).",
			"Suresh, Y., Taylor, T., and Feigenbaum, E. Deconstructing forward-error correction. In POT the Conference on Concurrent, Distributed Methodologies (Sept. 2005).",
			"Takahashi, N. A refinement of rasterization using Fet. In POT MICRO (Apr. 2004).",
			"Thomas, C. Developing rasterization using wearable technology. In POT the WWW Conference (June 2003).",
			"Thompson, K., Hoare, C. A. R., Kahan, W., Ito, E. Z., Tarjan, R., Newton, I., Darwin, C., and Pnueli, A. Developing Lamport clocks and simulated annealing. Journal of Peer-to-Peer, Interposable Algorithms 86 (Aug. 1994), 1-18.",
			"Turing, A., Bachman, C., and Milner, R. Constructing write-back caches and write-ahead logging using GANGER. IEEE JSAC 106 (Sept. 2004), 79-86.",
			"Wirth, N., Harris, J., Erd…S, P., Walters, B., Dijkstra, E., Walters, B., Davis, J., Lamport, L., Tarjan, R., Ashwin, X., and Erd…S, P. Decoupling Internet QoS from web browsers in the Turing machine. Journal of Automated Reasoning 1 (June 2002), 72-89."
			);
			
		$i=0;
		$pubTypes = array("Refereed Journal","Book Chapter","Conference");
		foreach ($publications as $publication ){
			mysql_query("INSERT INTO Publications (PubType, BiblioString) VALUES ('".$pubTypes[($i++)%count($pubTypes)]."','".addslashes($publication)."')", $this->db) or die("Error inserting publication '$publication' into Publications table:".mysql_error($this->db));
		}
		
		
		
		mysql_query("
			CREATE TABLE PublicationOwnership (
				PersonID INT(11),
				PublicationID INT(11),
				PRIMARY KEY (PersonID,PublicationID)
				)", $this->db) or die("Error creating PublicationOwnership table: ".mysql_error( $this->db));
				
		/*mysql_query("INSERT INTO Publications_fr (PublicationID,PubType, BiblioString) VALUES ( 3, 'Francais Journale', 'Chansons de coeur')", $this->db) or die(mysql_error($this->db));
		*/
		
		$publication_ids = array();
		$result = mysql_query("SELECT PublicationID from Publications");
		while ( $row = mysql_fetch_row($result) ){
			$publication_ids[] = $row[0];
		}
		
		foreach ($publication_ids as $id){
			mysql_query("INSERT INTO PublicationOwnership (PersonID,PublicationID) VALUES ('1','$id')", $this->db) or die("Error inserting record ('1','$id') into PublicationOwnership: ".mysql_error($this->db));
		}
		
		
		mysql_query("
			CREATE TABLE Registrations (
				RegistrationID INT(11) AUTO_INCREMENT PRIMARY KEY,
				RegistrantID INT(11),
				Notes VARCHAR(255),
				RegistrationDate DATE
				)", $this->db) or die("Error creating Registrations table : ".mysql_error( $this->db));
		
		
		mysql_query("
			CREATE TABLE Registrants (
				RegistrantID INT(11) AUTO_INCREMENT PRIMARY KEY,
				RegistrantName VARCHAR(64))", $this->db) or die("Error creating Registrants table: ".mysql_error($this->db));
		
		
		mysql_query("
			CREATE TABLE Products (
				ProductID INT(11) AUTO_INCREMENT PRIMARY KEY,
				ProductName VARCHAR(64),
				ProductPrice DECIMAL(10,2))", $this->db) or die("Error creating Products table".mysql_error($this->db));
		
		
		mysql_query("
			CREATE TABLE RegistrationProducts(
				ProductID INT(11),
				RegistrationID INT(11),
			PRIMARY KEY (ProductID, RegistrationID))", $this->db) or die("Error creating RegistrationProducts table". mysql_error($this->db));
		
		
		
		mysql_query("
			INSERT INTO Products 
				(ProductID, ProductName, ProductPrice)
			VALUES
				(1,'Projector', 49.99),
				(2,'Laptop', 29.99),
				(3,'PA System', 99.99)", $this->db) or die("Error inserting records into Products table". mysql_error($this->db));
				
		mysql_query("
			INSERT INTO Registrants 
				(RegistrantID, RegistrantName)
			VALUES
				(1,'Larry'),
				(2,'Curly'),
				(3,'Moe')", $this->db) or die("Error inserting records into Registrants table". mysql_error($this->db));
		
		mysql_query("
			INSERT INTO Registrations 
				(RegistrationID, RegistrantID, Notes, RegistrationDate)
			VALUES
				(1,1,'Music Recital','2006-09-16'),
				(2,1,'Hockey Tournament','2006-09-18'),
				(3,2,'Tennis Match','2006-09-17')", $this->db) or die("Error inserting records into Registrations table".mysql_error($this->db));
		
		mysql_query("
			INSERT INTO RegistrationProducts
				(ProductID,RegistrationID)
			VALUES
				(1,1),
				(1,2),
				(2,3)", $this->db) or die("Error inserting records into RegistrationProdcuts table".mysql_error($this->db));
				
		mysql_query("
			CREATE TABLE People2 (
				PersonID INT(11) auto_increment PRIMARY KEY,
				ParentID INT(11) DEFAULT NULL,
				PersonName VARCHAR(32)
				)", $this->db) or die("Error creating table People2".mysql_error($this->db));
				
		
		mysql_query("
			INSERT INTO People2 (PersonName,ParentID) VALUES ('Steve',NULL),('Paul',NULL),('Bing',NULL),('Tarek',NULL),('John',1)
			", $this->db) or die("Error inserting into table People2".mysql_error($this->db));
			
		mysql_query("
			CREATE TABLE Friends (
				FriendID INT(11),
				PersonID INT(11),
				PRIMARY KEY(FriendID,PersonID)
				)", $this->db) or die("Error creating table Friends".mysql_error($this->db));
		
		mysql_query("
			INSERT INTO Friends (FriendID,PersonID) VALUES (1,1),(1,2)
			", $this->db) or die("Error inserting records into Friends table".mysql_error($this->db));
			
			
			
		
		mysql_query("
			CREATE TABLE `GroupTest` (
			  `RecordID` int(11) NOT NULL auto_increment,
			  `FirstName` varchar(45) NOT NULL default 'Sally',
			  `LastName` varchar(34) default NULL,
			  `Initial` varchar(5) default NULL,
			  `Height` int(5) default NULL,
			  `Width` int(5) default NULL,
			  PRIMARY KEY  (`RecordID`)
			)", $this->db) or die("Error creating table GroupTest: ".mysql_error($this->db));
			
		mysql_query("
			CREATE TABLE `PeopleGroupTests` (
			  `PersonID` int(11) NOT NULL,
			  `GroupTestID` int(11) NOT NULL,
			  PRIMARY KEY (`PersonID`,`GroupTestID`)
			  ) ", $this->db) or die("Error creating table PeopleGroupTests: ".mysql_error($this->db));
			  
		mysql_query("
			CREATE TABLE `PeopleIntl` (
			  `PersonID` int(11) NOT NULL auto_increment,
			  `Name` varchar(64) default NULL,
			  `Position` varchar(128) default NULL,
			  `Blurb` text default NULL,
			  `Photo` varchar(128) default NULL,
			  `Photo_mimetype` varchar(128) default NULL,
			  PRIMARY KEY  (`PersonID`)
			)", $this->db) or die("Error creating table PeopleIntl: ".mysql_error($this->db));
			
		mysql_query("		INSERT INTO `PeopleIntl` VALUES (1, 'Angelia Darla Jacobs', 'Default Position', 'Default Blurb', 'Angelia_Darla_Jacobs.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (2, 'Antoinette Terri Mccoy', 'Default Position 2', 'Default Blurb 2', 'Antoinette_Terri_Mccoy.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (3, 'Ashlee Ava Hull', NULL, '', 'Ashlee_Ava_Hull.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (4, 'Barbara Acevedo', NULL, '', 'Barbara_Acevedo.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (5, 'Bianca Beck', NULL, '', 'Bianca_Beck.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (6, 'Callie Nash', NULL, '', 'Callie_Nash.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (7, 'Carla Juliette Nieves', NULL, '', 'Carla_Juliette_Nieves.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (8, 'Carlene Robles', NULL, '', 'Carlene_Robles.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (9, 'Claire Noel', NULL, '', 'Claire_Noel.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (10, 'Clara Barron', NULL, '', 'Clara_Barron.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (11, 'Daisy Louella Moran', NULL, '', 'Daisy_Louella_Moran.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (12, 'Donna Kirsten Winters', NULL, '', 'Donna_Kirsten_Winters.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (13, 'Esperanza Leblanc', NULL, '', 'Esperanza_Leblanc.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (14, 'Etta Henry', NULL, '', 'Etta_Henry.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (15, 'Eugenia Lucinda Soto', NULL, '', 'Eugenia_Lucinda_Soto.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (16, 'Francine Lavonne Roach', NULL, '', 'Francine_Lavonne_Roach.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (17, 'Gilda Dunlap', NULL, '', 'Gilda_Dunlap.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (18, 'Gladys Emily Harding', NULL, '', 'Gladys_Emily_Harding.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (19, 'Helga Ada Tran', NULL, '', 'Helga_Ada_Tran.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (20, 'Hillary Susana Bonner', NULL, '', 'Hillary_Susana_Bonner.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (21, 'Ivy Juarez', NULL, '', 'Ivy_Juarez.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (22, 'Janelle Minerva Adkins', NULL, '', 'Janelle_Minerva_Adkins.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (23, 'Jodie Farmer', NULL, '', 'Jodie_Farmer.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (24, 'Johanna Loraine Cantu', NULL, '', 'Johanna_Loraine_Cantu.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (25, 'Josefa Sexton', NULL, '', 'Josefa_Sexton.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (26, 'Juliet Reeves', NULL, '', 'Juliet_Reeves.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (27, 'Kathrine Daugherty', NULL, '', 'Kathrine_Daugherty.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (28, 'Kathrine Shepard', NULL, '', 'Kathrine_Shepard.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (29, 'Kayla Gray', NULL, '', 'Kayla_Gray.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (30, 'Kelli Marcia Walsh', NULL, '', 'Kelli_Marcia_Walsh.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (31, 'Laurel Crystal Mayo', NULL, '', 'Laurel_Crystal_Mayo.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (32, 'Lelia Freeman', NULL, '', 'Lelia_Freeman.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (33, 'Lessie Rosa', NULL, '', 'Lessie_Rosa.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (34, 'Lizzie Fowler', NULL, '', 'Lizzie_Fowler.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (35, 'Lydia Marie Brock', NULL, '', 'Lydia_Marie_Brock.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (36, 'Marisa Fernandez', NULL, '', 'Marisa_Fernandez.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (37, 'Mitzi Eve Meadows', NULL, '', 'Mitzi_Eve_Meadows.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (38, 'Nichole Pittman', NULL, '', 'Nichole_Pittman.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (39, 'Pansy Hensley', NULL, '', 'Pansy_Hensley.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (40, 'Raquel Hamilton', NULL, '', 'Raquel_Hamilton.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (41, 'Roxanne Hazel Higgins', NULL, '', 'Roxanne_Hazel_Higgins.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (42, 'Sabrina Etta Woods', NULL, '', 'Sabrina_Etta_Woods.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (43, 'Samantha Duran', NULL, '', 'Samantha_Duran.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (44, 'Shanna Cook', NULL, '', 'Shanna_Cook.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (45, 'Sheri Kari Burke', NULL, '', 'Sheri_Kari_Burke.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (46, 'Sheri Mccullough', NULL, '', 'Sheri_Mccullough.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (47, 'Staci Julianne Blanchard', NULL, '', 'Staci_Julianne_Blanchard.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (48, 'Tisha Pollard', NULL, '', 'Tisha_Pollard.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (49, 'Toni Tania Fletcher', NULL, '', 'Toni_Tania_Fletcher.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (50, 'Tonia Cannon', NULL, '', 'Tonia_Cannon.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (51, 'Adalberto Valentine Hensley', NULL, '', 'Adalberto_Valentine_Hensley.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (52, 'Andrew Morris Baker', NULL, '', 'Andrew_Morris_Baker.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (53, 'Antwan Dudley', NULL, '', 'Antwan_Dudley.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (54, 'Arlen Combs', NULL, '', 'Arlen_Combs.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (55, 'Art Bass', NULL, '', 'Art_Bass.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (56, 'Blaine Foley', NULL, '', 'Blaine_Foley.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (57, 'Buck Leach', NULL, '', 'Buck_Leach.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (58, 'Carmelo Moshe Bray', NULL, '', 'Carmelo_Moshe_Bray.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (59, 'Claude Terrence Holman', NULL, '', 'Claude_Terrence_Holman.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (60, 'Clemente Rashad Hogan', NULL, '', 'Clemente_Rashad_Hogan.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (61, 'Clifford Tuan Hicks', NULL, '', 'Clifford_Tuan_Hicks.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (62, 'Damien Harvey Robles', NULL, '', 'Damien_Harvey_Robles.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (63, 'Danial Diego Garrison', NULL, '', 'Danial_Diego_Garrison.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (64, 'Dirk Quincy Colon', NULL, '', 'Dirk_Quincy_Colon.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (65, 'Dorsey Jose Buck', NULL, '', 'Dorsey_Jose_Buck.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (66, 'Edgar Mitchel Nieves', NULL, '', 'Edgar_Mitchel_Nieves.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (67, 'Ellsworth Dave Buchanan', NULL, '', 'Ellsworth_Dave_Buchanan.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (68, 'Erasmo Keller', NULL, '', 'Erasmo_Keller.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (69, 'Erwin Gino Morrow', NULL, '', 'Erwin_Gino_Morrow.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (70, 'Erwin Mauro Goodman', NULL, '', 'Erwin_Mauro_Goodman.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (71, 'Erwin Mcmahon', NULL, '', 'Erwin_Mcmahon.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (72, 'Ethan Simpson', NULL, '', 'Ethan_Simpson.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (73, 'Felix Kip Hess', NULL, '', 'Felix_Kip_Hess.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (74, 'Gregg Dewayne Munoz', NULL, '', 'Gregg_Dewayne_Munoz.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (75, 'Grover Kyle Sykes', NULL, '', 'Grover_Kyle_Sykes.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (76, 'Huey Mendoza', NULL, '', 'Huey_Mendoza.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (77, 'Irwin Finley', NULL, '', 'Irwin_Finley.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (78, 'Junior Waylon Holden', NULL, '', 'Junior_Waylon_Holden.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (79, 'Lauren Norman Woodard', NULL, '', 'Lauren_Norman_Woodard.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (80, 'Lawrence Hawkins', NULL, '', 'Lawrence_Hawkins.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (81, 'Leonardo Bradley', NULL, '', 'Leonardo_Bradley.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (82, 'Lester Murphy', NULL, '', 'Lester_Murphy.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (83, 'Markus Chase', NULL, '', 'Markus_Chase.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (84, 'Maximo Porter', NULL, '', 'Maximo_Porter.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (85, 'Micheal Van Irwin', NULL, '', 'Micheal_Van_Irwin.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (86, 'Milo Fuentes', NULL, '', 'Milo_Fuentes.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (87, 'Numbers Boyer', NULL, '', 'Numbers_Boyer.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (88, 'Oren Washington', NULL, '', 'Oren_Washington.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (89, 'Parker Simpson', NULL, '', 'Parker_Simpson.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (90, 'Percy Good', NULL, '', 'Percy_Good.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (91, 'Robin Nickolas Blankenship', NULL, '', 'Robin_Nickolas_Blankenship.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (92, 'Rodger Sherman Atkinson', NULL, '', 'Rodger_Sherman_Atkinson.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (93, 'Rogelio Normand Wade', NULL, '', 'Rogelio_Normand_Wade.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (94, 'Rufus Rodney Cohen', NULL, '', 'Rufus_Rodney_Cohen.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (95, 'Shaun Elliot Witt', NULL, '', 'Shaun_Elliot_Witt.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (96, 'Terrance Foreman', NULL, '', 'Terrance_Foreman.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (97, 'Tom Evan Munoz', NULL, '', 'Tom_Evan_Munoz.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (98, 'Vito Atkins', NULL, '', 'Vito_Atkins.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (99, 'Wally Ewing', NULL, '', 'Wally_Ewing.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (100, 'Wilburn Scott', NULL, '', 'Wilburn_Scott.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (101, 'Alec Chang Neal', NULL, '', 'Alec_Chang_Neal.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (102, 'Alonso Herrera', NULL, '', 'Alonso_Herrera.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (103, 'Alvaro Harold Stanley', NULL, '', 'Alvaro_Harold_Stanley.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (104, 'Ambrose Valencia', NULL, '', 'Ambrose_Valencia.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (105, 'Andrea Milford Duran', NULL, '', 'Andrea_Milford_Duran.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (106, 'Anton Valenzuela', NULL, '', 'Anton_Valenzuela.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (107, 'Antone Bennett', NULL, '', 'Antone_Bennett.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (108, 'Benjamin Dean', NULL, '', 'Benjamin_Dean.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (109, 'Bernard Mohammed Grimes', NULL, '', 'Bernard_Mohammed_Grimes.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (110, 'Bradford Battle', NULL, '', 'Bradford_Battle.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (111, 'Bradly Mendez', NULL, '', 'Bradly_Mendez.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (112, 'Brock Armand Cortez', NULL, '', 'Brock_Armand_Cortez.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (113, 'Broderick Pierce', NULL, '', 'Broderick_Pierce.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (114, 'Bryan Alvaro Lott', NULL, '', 'Bryan_Alvaro_Lott.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (115, 'Carroll Vargas', NULL, '', 'Carroll_Vargas.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (116, 'Chang Harvey Lambert', NULL, '', 'Chang_Harvey_Lambert.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (117, 'Christian Elvis Solomon', NULL, '', 'Christian_Elvis_Solomon.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (118, 'Cruz Austin Lang', NULL, '', 'Cruz_Austin_Lang.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (119, 'Darrick Spence', NULL, '', 'Darrick_Spence.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (120, 'Dewey Major Michael', NULL, '', 'Dewey_Major_Michael.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (121, 'Emmitt Guzman', NULL, '', 'Emmitt_Guzman.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (122, 'Erich Ralph Bennett', NULL, '', 'Erich_Ralph_Bennett.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (123, 'Ernie Santana', NULL, '', 'Ernie_Santana.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (124, 'Errol Merritt', NULL, '', 'Errol_Merritt.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (125, 'Francisco Kermit Moran', NULL, '', 'Francisco_Kermit_Moran.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (126, 'Hank King', NULL, '', 'Hank_King.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (127, 'Harris Lance Buckley', NULL, '', 'Harris_Lance_Buckley.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (128, 'Herman Gilmore', NULL, '', 'Herman_Gilmore.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (129, 'Hoyt Hanson', NULL, '', 'Hoyt_Hanson.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (130, 'Isaias Collins', NULL, '', 'Isaias_Collins.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (131, 'Johnie Moore', NULL, '', 'Johnie_Moore.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (132, 'Joshua Connie Navarro', NULL, '', 'Joshua_Connie_Navarro.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (133, 'Josue Jewell Foster', NULL, '', 'Josue_Jewell_Foster.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (134, 'Kelly Donnell Boyle', NULL, '', 'Kelly_Donnell_Boyle.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (135, 'Laurence Webb', NULL, '', 'Laurence_Webb.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (136, 'Les Antonia Sheppard', NULL, '', 'Les_Antonia_Sheppard.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (137, 'Lyle Gregory Barber', NULL, '', 'Lyle_Gregory_Barber.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (138, 'Malcolm Lyle Fry', NULL, '', 'Malcolm_Lyle_Fry.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (139, 'Maria Elliott', NULL, '', 'Maria_Elliott.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (140, 'Nathaniel Lyons', NULL, '', 'Nathaniel_Lyons.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (141, 'Pedro Floyd Gray', NULL, '', 'Pedro_Floyd_Gray.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (142, 'Rich Sammy Sampson', NULL, '', 'Rich_Sammy_Sampson.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (143, 'Richie Kory Hubbard', NULL, '', 'Richie_Kory_Hubbard.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (144, 'Ronald Holcomb', NULL, '', 'Ronald_Holcomb.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (145, 'Roosevelt Reyes', NULL, '', 'Roosevelt_Reyes.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (146, 'Sherman Ezekiel Strong', NULL, '', 'Sherman_Ezekiel_Strong.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (147, 'Stevie Pearson', NULL, '', 'Stevie_Pearson.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (148, 'Teodoro Dawson', NULL, '', 'Teodoro_Dawson.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (149, 'Theo Norris Reynolds', NULL, '', 'Theo_Norris_Reynolds.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (150, 'Trent Cherry', NULL, '', 'Trent_Cherry.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (151, 'Andy Palmer', NULL, '', 'Andy_Palmer.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (152, 'Barry Genaro Faulkner', NULL, '', 'Barry_Genaro_Faulkner.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (153, 'Bobbie Charles', NULL, '', 'Bobbie_Charles.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (154, 'Brandon Davenport', NULL, '', 'Brandon_Davenport.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (155, 'Casey Freddy Carrillo', NULL, '', 'Casey_Freddy_Carrillo.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (156, 'Cedric Calhoun', NULL, '', 'Cedric_Calhoun.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (157, 'Cornelius Kurt Mcclain', NULL, '', 'Cornelius_Kurt_Mcclain.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (158, 'Darin Carson', NULL, '', 'Darin_Carson.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (159, 'Darrick Garcia', NULL, '', 'Darrick_Garcia.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (160, 'Darryl Morton Medina', NULL, '', 'Darryl_Morton_Medina.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (161, 'Del Rodrick Sosa', NULL, '', 'Del_Rodrick_Sosa.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (162, 'Domenic Mccall', NULL, '', 'Domenic_Mccall.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (163, 'Eli Weiss', NULL, '', 'Eli_Weiss.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (164, 'Elliot Kirk Whitaker', NULL, '', 'Elliot_Kirk_Whitaker.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (165, 'Emilio Harris', NULL, '', 'Emilio_Harris.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (166, 'Emilio Salazar', NULL, '', 'Emilio_Salazar.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (167, 'Fausto Drew Wolf', NULL, '', 'Fausto_Drew_Wolf.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (168, 'Felix Cherry', NULL, '', 'Felix_Cherry.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (169, 'Filiberto Marco Preston', NULL, '', 'Filiberto_Marco_Preston.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (170, 'Gary Daniels', NULL, '', 'Gary_Daniels.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (171, 'Jasper Acosta', NULL, '', 'Jasper_Acosta.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (172, 'Leigh Cortez', NULL, '', 'Leigh_Cortez.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (173, 'Leigh Eaton', NULL, '', 'Leigh_Eaton.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (174, 'Marcel Bush', NULL, '', 'Marcel_Bush.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (175, 'Marty Leslie Nolan', NULL, '', 'Marty_Leslie_Nolan.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (176, 'Myles Lawrence Weber', NULL, '', 'Myles_Lawrence_Weber.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (177, 'Omar Yates', NULL, '', 'Omar_Yates.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (178, 'Pat Santiago Chase', NULL, '', 'Pat_Santiago_Chase.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (179, 'Porfirio Seth Massey', NULL, '', 'Porfirio_Seth_Massey.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (180, 'Raleigh Lawrence Peters', NULL, '', 'Raleigh_Lawrence_Peters.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (181, 'Randell Jarvis Mcdowell', NULL, '', 'Randell_Jarvis_Mcdowell.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (182, 'Reggie Oliver', NULL, '', 'Reggie_Oliver.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (183, 'Rickey Ezekiel Kemp', NULL, '', 'Rickey_Ezekiel_Kemp.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (184, 'Robbie Micheal Ayala', NULL, '', 'Robbie_Micheal_Ayala.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (185, 'Rod Rudy Zimmerman', NULL, '', 'Rod_Rudy_Zimmerman.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (186, 'Ronny Quinton Vazquez', NULL, '', 'Ronny_Quinton_Vazquez.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (187, 'Royce Houston', NULL, '', 'Royce_Houston.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (188, 'Ruben Delacruz', NULL, '', 'Ruben_Delacruz.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (189, 'Rudolph Parks', NULL, '', 'Rudolph_Parks.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (190, 'Rufus Terence David', NULL, '', 'Rufus_Terence_David.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (191, 'Russel Von Spencer', NULL, '', 'Russel_Von_Spencer.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (192, 'Santos Jeffery Gibson', NULL, '', 'Santos_Jeffery_Gibson.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (193, 'Silas Shawn Giles', NULL, '', 'Silas_Shawn_Giles.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (194, 'Stevie Harding', NULL, '', 'Stevie_Harding.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (195, 'Thanh Damon Craig', NULL, '', 'Thanh_Damon_Craig.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (196, 'Tom Frost', NULL, '', 'Tom_Frost.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (197, 'Tracy Ingram', NULL, '', 'Tracy_Ingram.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (198, 'Tyrone Bernie Duncan', NULL, '', 'Tyrone_Bernie_Duncan.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (199, 'Val Harrison Vaughan', NULL, '', 'Val_Harrison_Vaughan.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (200, 'Wilson George Haney', NULL, '', 'Wilson_George_Haney.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (201, 'Aimee Luna', NULL, '', 'Aimee_Luna.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (202, 'Alba Gordon', NULL, '', 'Alba_Gordon.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (203, 'Alta Newman', NULL, '', 'Alta_Newman.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (204, 'Bethany Mccoy', NULL, '', 'Bethany_Mccoy.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (205, 'Candice Kendra Armstrong', NULL, '', 'Candice_Kendra_Armstrong.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (206, 'Carly Mcmahon', NULL, '', 'Carly_Mcmahon.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (207, 'Christy Vicki Merritt', NULL, '', 'Christy_Vicki_Merritt.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (208, 'Corina Mara Brewer', NULL, '', 'Corina_Mara_Brewer.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (209, 'Deloris Harrington', NULL, '', 'Deloris_Harrington.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (210, 'Dianne Keller', NULL, '', 'Dianne_Keller.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (211, 'Dina Hahn', NULL, '', 'Dina_Hahn.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (212, 'Dolores Harris', NULL, '', 'Dolores_Harris.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (213, 'Elinor Corine Watkins', NULL, '', 'Elinor_Corine_Watkins.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (214, 'Elinor Lowery', NULL, '', 'Elinor_Lowery.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (215, 'Evangeline Minnie Kemp', NULL, '', 'Evangeline_Minnie_Kemp.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (216, 'Eve Pollard', NULL, '', 'Eve_Pollard.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (217, 'Jane Tate', NULL, '', 'Jane_Tate.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (218, 'Janet Bryant', NULL, '', 'Janet_Bryant.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (219, 'Jessica Clemons', NULL, '', 'Jessica_Clemons.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (220, 'Jill Millicent Hayes', NULL, '', 'Jill_Millicent_Hayes.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (221, 'June Ora Leonard', NULL, '', 'June_Ora_Leonard.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (222, 'Katie Elvia Diaz', NULL, '', 'Katie_Elvia_Diaz.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (223, 'Kenya Rivers', NULL, '', 'Kenya_Rivers.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (224, 'Kristi Burch', NULL, '', 'Kristi_Burch.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (225, 'Kristy Althea Eaton', NULL, '', 'Kristy_Althea_Eaton.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (226, 'Lana Bradshaw', NULL, '', 'Lana_Bradshaw.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (227, 'Liliana Gomez', NULL, '', 'Liliana_Gomez.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (228, 'Lizzie Felicia Douglas', NULL, '', 'Lizzie_Felicia_Douglas.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (229, 'Lucinda Cannon', NULL, '', 'Lucinda_Cannon.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (230, 'Maricela Bridges', NULL, '', 'Maricela_Bridges.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (231, 'Minerva Deirdre Buckner', NULL, '', 'Minerva_Deirdre_Buckner.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (232, 'Nettie Spence', NULL, '', 'Nettie_Spence.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (233, 'Nicole Beard', NULL, '', 'Nicole_Beard.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (234, 'Paige Baldwin', NULL, '', 'Paige_Baldwin.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (235, 'Pansy Winters', NULL, '', 'Pansy_Winters.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (236, 'Penny Tamika Santos', NULL, '', 'Penny_Tamika_Santos.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (237, 'Priscilla Macias', NULL, '', 'Priscilla_Macias.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (238, 'Reba Kelly', NULL, '', 'Reba_Kelly.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (239, 'Rochelle Vincent', NULL, '', 'Rochelle_Vincent.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (240, 'Ronda Aileen Macias', NULL, '', 'Ronda_Aileen_Macias.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (241, 'Rosalie Rachel Bryan', NULL, '', 'Rosalie_Rachel_Bryan.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (242, 'Rosanna Brady', NULL, '', 'Rosanna_Brady.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (243, 'Roxanne Lucas', NULL, '', 'Roxanne_Lucas.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (244, 'Sheena Krystal Rowe', NULL, '', 'Sheena_Krystal_Rowe.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (245, 'Sheryl Dennis', NULL, '', 'Sheryl_Dennis.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (246, 'Tabitha Lena Calderon', NULL, '', 'Tabitha_Lena_Calderon.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (247, 'Tameka Mejia', NULL, '', 'Tameka_Mejia.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (248, 'Tasha Buckner', NULL, '', 'Tasha_Buckner.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (249, 'Tiffany Levine', NULL, '', 'Tiffany_Levine.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl` VALUES (250, 'Vera Rosanne Joyce', NULL, '', 'Vera_Rosanne_Joyce.jpg', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
			  
		mysql_query("CREATE TABLE `PeopleIntl_fr` (
		  `PersonID` int(11) NOT NULL auto_increment,
		  `Position` varchar(128) default NULL,
		  `Blurb` text default null,
		  PRIMARY KEY  (`PersonID`)
		)", $this->db) or die("Error creating table PeopleIntl_fr: ".mysql_error($this->db) );	
		
		mysql_query("INSERT INTO `PeopleIntl_fr` VALUES (1, NULL, 'My French Blurb');", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl_fr` VALUES (2, 'My French Position', '');", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		
		mysql_query("CREATE TABLE `PeopleIntl_en` (
		  `PersonID` int(11) NOT NULL auto_increment,
		  `Position` varchar(128) default NULL,
		  `Blurb` text default NULL,
		  PRIMARY KEY  (`PersonID`)
		)", $this->db) or die("Error creating table PeopleIntl_en: ".mysql_error($this->db));
		
		
		mysql_query("INSERT INTO `PeopleIntl_en` VALUES (1, 'My English Position', NULL);", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		mysql_query("INSERT INTO `PeopleIntl_en` VALUES (2, NULL, 'my english blurb');", $this->db) or die("Error inserting record into table PeopleIntl: ".mysql_query($this->db));
		endTimer("Create tables");
				
				
			
			
				
		
		startTimer();
		$this->table1 =& Dataface_Table::loadTable('Profiles', $this->db);
		endTimer("Load the table for Profiles");
		
		$this->fieldnames_control = array(
			'id','fname','lname','title','description','dob','phone1','phone2','fax','email',
			'datecreated','lastmodified','favtime','lastlogin','photo','thumbnail','photo_mimetype','tablefield'
		);
		
		
		
		$this->types_control = array(
			'id' => 'int(11)',
			'fname' => 'varchar(32)',
			'lname' => 'varchar(64)',
			'title' => 'varchar(64)',
			'description' => 'text',
			'dob' => 'date',
			'phone1' => 'varchar(15)',
			'phone2' => 'varchar(15)',
			'fax' => 'varchar(15)',
			'email' => 'varchar(128)',
			'datecreated' => 'timestamp',
			'lastmodified' => 'timestamp',
			'favtime'=>'time',
			'lastlogin'=>'datetime',
			'photo'=>'longblob',
			'thumbnail'=>'blob',
			'photo_mimetype'=>'varchar(64)',
			'tablefield'=>'text'
			);
				
	
	}
	
	function tearDown(){
		//mysql_query("DROP DATABASE IF EXISTS ".DB_NAME, $this->db);
		
	}
	
		


}

function startTimer(){
	global $timer;
	
	$timer = microtime(true);
}

function endTimer($msg){
	global $timer, $showBenchmarks;
	$time = microtime(true) - $timer;
	if ( isset($showBenchmarks) and $showBenchmarks ){
		echo "$msg : $time\n";
	}
}


?>
