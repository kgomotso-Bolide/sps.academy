/* ---- Software Developer: the twelve knowledge modules -----------------------
   Generated from the provider's QCTO curriculum document (curriculum
   251201-005-00, SAQA 118707, "Reviewed 2025" pack) on 20 Nov 2026: SECTION
   3A, which gives the modules, their credits and NQF levels, each topic and its
   weighting, and the topic elements. These are the REGISTERED figures, not
   marketing copy, and must not be hand-edited. Every module's weightings sum
   to 100 and the credits sum to 66; both are asserted at the bottom of this
   file, so a bad edit fails loudly.

   The qualification is 220 credits in total: 66 knowledge, 89 practical skill
   (PM-01 to PM-14, listed in lib/submissions.php) and 65 work experience.
   Only the knowledge modules are studied on the site.

   ONE OUTRIGHT DISAGREEMENT IN THE SOURCE: SECTION 1 lists KM-03 at NQF
   Level 5; SECTION 3A, which this file is generated from, says Level 4.
   Level 4 is used. KM-03 (webscraping) carries a purpose statement about
   "the design aspect applicable to various types of publications and
   documents" in the source; it is kept as printed. In KM-06 the topic codes are printed "KM-06-KT-01" with an
   extra hyphen; they are read as KM-06-KT01. Topics are numbered by the section
   they sit under, never by the code printed in the heading.

   What is deliberately NOT here: the teaching prose. That is in the learner
   guides, which are Centenary's material and access-controlled.

   ---- TO PUT A DOCUMENT LIVE ----
   Not here. Upload it on the Material page under Administration; materials.php
   hands it only to a signed-in learner who is enrolled. Learner guides and
   workbooks only: assessment papers, marking memos and facilitator guides are
   never published. */
(function () {

  const MODULES = [
  {
    id: "KM-01", code: "251201-005-00-KM-01",
    title: "Computers and Computing Systems",
    nqf: 4, credits: 12, hours: 120,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of what computers can do and the processes that make them function in terms of the four major parts: input, output, CPU (central processing unit) and memory. It gives an overview of networks and connectivity as well as security issues pertaining to IT ecosystems.",
    topics: [
      { code: "KM-01-KT01", n: "Problem solving skills for IT Professionals", w: 5,
        covers: [
          "Identification of the problem",
          "Establishing a probable cause",
          "Testing to determine the cause",
          "Establishing a plan to resolve the problem",
          "Implementing your solution",
          "Verifying functionality",
          "Implementing preventative measures",
          "Documenting results"
        ] },
      { code: "KM-01-KT02", n: "Techniques for safety", w: 5,
        covers: [
          "Personal protective equipment",
          "Chemicals",
          "Cables",
          "Hazards (Tripping, electrical, fire, jewellery, etc.)",
          "Environmental risks",
          "Ergonomics and lifting techniques"
        ] },
      { code: "KM-01-KT03", n: "System components", w: 5,
        covers: [
          "Storage devices such as hard drives, magnetic tapes, flash drives",
          "Solid-state drives such as IDE, SATA, and SCSI",
          "Processors and CPUs, processing speeds, operating systems, and controls"
        ] },
      { code: "KM-01-KT04", n: "Motherboards", w: 5,
        covers: [
          "Function of motherboard",
          "Location of primary components",
          "Motherboard chipsets, audio connections, drive activity light, the power light, on/off"
        ] },
      { code: "KM-01-KT05", n: "Processors", w: 5,
        covers: [
          "Processors",
          "Terminology",
          "Processor speeds in megahertz and gigahertz",
          "Architecture, including registers, cache memory, virtualization, graphics processing units,"
        ] },
      { code: "KM-01-KT06", n: "Memory", w: 5,
        covers: [
          "Types of memory: RAM and ROM including DRAM, SRAM, CMOS RAM, and VRAM",
          "Purpose of memory"
        ] },
      { code: "KM-01-KT07", n: "BIOS and CMOS", w: 5,
        covers: [
          "BIOS: Basic Input-Output System that runs a computer",
          "Upgrade BIOS",
          "Potential issues",
          "CMOS: Complementary Metal Oxide Semiconductor",
          "CMOS setup, settings, and access"
        ] },
      { code: "KM-01-KT08", n: "Hard drives and storage devices", w: 5,
        covers: [
          "Hard drives and storage devices",
          "Installing discs, formatting discs, partitioning discs, troubleshooting disks, as well as IDE,",
          "Parts of a hard disk, such as platters, tracks, sectors, clusters, and cylinders",
          "Installing IDE devices"
        ] },
      { code: "KM-01-KT09", n: "Power supplies and voltage", w: 5,
        covers: [
          "Power supply units, uninterruptible power supplies",
          "Identifying power supplies",
          "Amps, ohms, volts, and watts",
          "Affecting system hardware and components"
        ] },
      { code: "KM-01-KT10", n: "Ports, cables, and connectors", w: 2,
        covers: [
          "Ports, cables, and connectors",
          "Power supply units, uninterruptible power supplies, and identifying power supplies",
          "Conversion of voltage, overcurrent protection, input supply voltage, DC voltage"
        ] },
      { code: "KM-01-KT11", n: "Networking and network operating systems", w: 5,
        covers: [
          "Connect to a LAN using WiFi",
          "Connect to a Lan using Lan Cable",
          "Checking Network Card for Connectivity",
          "Connecting to WAN/Internet via Dongle",
          "Connect to WAN/Internet via HotSpot",
          "Sharing a Network Connection",
          "Sharing a Folder across a network",
          "Client/Server Environment",
          "Domain vs Computer Authentication",
          "Single Sign-On",
          "Network File Shares",
          "LAN vs WAN vs Cloud",
          "Securing your identity"
        ] },
      { code: "KM-01-KT12", n: "Networking and wireless connections", w: 3,
        covers: [
          "Peer-to-peer networks and server networks",
          "Common functionality of server networks",
          "Networking devices including network interface cards, repeaters, bridges, routers,"
        ] },
      { code: "KM-01-KT13", n: "Input and output devices", w: 3,
        covers: [
          "Input devices and function",
          "Output devices and function",
          "Keyboards, mouses, and touchscreens"
        ] },
      { code: "KM-01-KT14", n: "Installing and managing printers", w: 2,
        covers: [
          "Types of printers",
          "Installing and managing printers",
          "Drivers, paper feed, networking, and user error"
        ] },
      { code: "KM-01-KT15", n: "Mobile devices, multimedia, and laptop computers", w: 2,
        covers: [
          "Mobile devices",
          "Multimedia",
          "Laptop computers",
          "Troubleshooting common problems: battery or power supply issues, verifying"
        ] },
      { code: "KM-01-KT16", n: "Preventative maintenance", w: 2,
        covers: [
          "Purpose of preventive maintenance",
          "Benefits",
          "Prevent data loss",
          "Improve performance",
          "Preventive tasks: e.g. Updating virus and malware detectors, backing up data,"
        ] },
      { code: "KM-01-KT17", n: "Troubleshooting procedures", w: 2,
        covers: [
          "Troubleshooting process",
          "Hardware tools required",
          "Software tools e.g. boot disks and system rescue, etc."
        ] },
      { code: "KM-01-KT18", n: "Operating systems", w: 5,
        covers: [
          "Functions of an operating system: e.g. managing hardware, resources, data, and making",
          "Big three operating systems: Windows, Linux, and Mac",
          "Control panel",
          "Remote assistance",
          "OS command prompt",
          "OS migration",
          "OS configurations"
        ] },
      { code: "KM-01-KT19", n: "Managing files", w: 2,
        covers: [
          "Files and directories, naming conventions, and the File Allocation Table (FAT)",
          "Common executable file extensions, such as batch files, command files, and power shell",
          "Copying, moving, deleting and archiving (ZIP) files and folders"
        ] },
      { code: "KM-01-KT20", n: "Applications utility, troubleshooting, and optimization", w: 2,
        covers: [
          "Upgrade and remove applications from computers",
          "New installs through disc or internet download",
          "Common processes for downloading and removing software",
          "Troubleshooting",
          "Optimisation"
        ] },
      { code: "KM-01-KT21", n: "Configuring device drivers", w: 5,
        covers: [
          "Configuring device drivers",
          "Changing device settings",
          "Updating drivers",
          "Removing device drivers",
          "Plug-and-play devices",
          "Device failure",
          "System restore points"
        ] },
      { code: "KM-01-KT22", n: "Recovery", w: 5,
        covers: [
          "Recovering systems and disaster recovery",
          "Process for disaster recovery",
          "Expecting things to go wrong",
          "Using boot logs to troubleshoot problems",
          "Process for booting a system into safe mode",
          "Emergency repair",
          "Factory repair partitions",
          "Startup repair",
          "System image recovery"
        ] },
      { code: "KM-01-KT23", n: "Cloud computing", w: 5,
        covers: [
          "Network or internet connection",
          "Manipulate, collate, configure, and access applications online",
          "Providing support infrastructure, applications, and data storage",
          "Benefits and risks to cloud computing as well as cloud characteristics",
          "Cloud storage"
        ] },
      { code: "KM-01-KT24", n: "Security fundamentals", w: 5,
        covers: [
          "Business continuity",
          "Disaster recovery planning",
          "Application development security",
          "Access control",
          "Information security governance",
          "Risk management",
          "Operation security",
          "Physical security",
          "Network security"
        ] },
      { code: "KM-01-KT25", n: "Programming and development", w: 5,
        covers: [
          "Software development",
          "Game development",
          "Software tools",
          "Mobile Apps",
          "Internet of things"
        ] }
    ]
  },
  {
    id: "KM-02", code: "251201-005-00-KM-02",
    title: "Desktop and Professional Software to Communicate and Visualise Information",
    nqf: 4, credits: 8, hours: 80,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of the functioning and purpose of information, computer technology and computer hardware units. The learning of this module will also enable the learner to acquire an understanding of the principles of electronic communication and the operation and functioning of software packages, including the design of presentations.",
    topics: [
      { code: "KM-02-KT01", n: "Electronic Communication", w: 15,
        covers: [
          "Internet",
          "Web sites",
          "Internet service providers",
          "Electronic mail",
          "Internet forums",
          "Digital learning",
          "Virtual communication: virtual meetings, virtual workshops, conferencing, etc."
        ] },
      { code: "KM-02-KT02", n: "Software Apps for office use", w: 15,
        covers: [
          "Electronic text documents",
          "Electronic spreadsheets",
          "Electronic desktop databases",
          "Electronic presentation tools",
          "Internet access",
          "Electronic written communication",
          "Electronic meetings",
          "Electronic seminars"
        ] },
      { code: "KM-02-KT03", n: "Operating a software package", w: 15,
        covers: [
          "Create new folders",
          "Move files",
          "Format diskettes",
          "Copy files",
          "Open files and folders",
          "Create folders and files",
          "Undo commands",
          "Find files",
          "Recycle bin"
        ] },
      { code: "KM-02-KT04", n: "Text documents using an appropriate software package", w: 40,
        covers: [
          "Select text",
          "Replace text",
          "Insert text",
          "Insert and format page numbers across sections",
          "Format text",
          "Format paragraphs",
          "Format stylesheets",
          "Format page layouts",
          "Find and replace text",
          "Align text",
          "Use tabs",
          "Insert special symbols",
          "Move, cut and paste text",
          "Format size and appearance of text",
          "Create borders",
          "Change background colours",
          "Create and format tables",
          "Print documents with different layouts",
          "Mail merge documents from different data sources",
          "Insert tables of contents, citations, list of figures and list of tables",
          "Insert bookmarks",
          "Insert comments",
          "Track, accept/reject changes",
          "Spell and grammar check document"
        ] },
      { code: "KM-02-KT05", n: "Presentations using and appropriate software package", w: 15,
        covers: [
          "Layout and design",
          "Shapes, smart art and charts",
          "Text and text box",
          "Illustrations and visuals",
          "Smart texts",
          "Transitions",
          "Animation",
          "Slide show"
        ] }
    ]
  },
  {
    id: "KM-03", code: "251201-005-00-KM-03",
    title: "Automated Webscraping as a Data Source",
    nqf: 4, credits: 8, hours: 80,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of the design aspect applicable to various types of publications and documents.",
    topics: [
      { code: "KM-03-KT01", n: "Sources of data", w: 10,
        covers: [
          "Sources and collection systems",
          "Data capturing systems and processes",
          "Parameters for data capturing systems and processes",
          "Maintenance of data capturing systems and processes"
        ] },
      { code: "KM-03-KT02", n: "Webscraping and access to accurate data", w: 10,
        covers: [
          "Electronically identifying and classifying documents",
          "Capturing data from documents",
          "Ensuring users only see the information they need",
          "Indexing data for ease of search and retrieval",
          "Validating data",
          "Web Scraping Fundamentals"
        ] },
      { code: "KM-03-KT03", n: "Big Data", w: 10,
        covers: [
          "Volume, velocity variety and veracity",
          "Sources",
          "Applications",
          "Benefits"
        ] },
      { code: "KM-03-KT04", n: "Data quality", w: 10,
        covers: [
          "Ten quality traits: accuracy, accessibility, comprehensiveness, consistency, currency,",
          "Data quality best practices"
        ] },
      { code: "KM-03-KT05", n: "Best practices for data governance", w: 10,
        covers: [
          "Comprehensive security with multi-level access control and management",
          "Data breaches",
          "Data loss"
        ] },
      { code: "KM-03-KT06", n: "Legislation (e.g. POPI Act)", w: 25,
        covers: [
          "Lawful collection of data – transparency and accountability requirements",
          "Data protection principles",
          "Legal requirements and restrictions pertaining to",
          "The rights of individuals",
          "Staff information and customer information",
          "Data held by an organisation",
          "Gathering information for marketing purposes",
          "Special categories of personal data ( e.g. Medical and health information, genetic data,",
          "Restrictions on sending personal data abroad",
          "Outsourcing personal data processing operations, e.g. Payroll, call-centres, etc.",
          "Transferring data to 3rd parties",
          "Data retention",
          "Disclosures required by law",
          "Data protection impact assessment"
        ] },
      { code: "KM-03-KT07", n: "Wrangling", w: 15,
        covers: [
          "Importing data from different file formats",
          "Web scraping",
          "How to tidy data using suitable software packages to better facilitate analysis",
          "String processing with regular expressions (regex)",
          "HTML parsing",
          "Wrangle data using suitable software package",
          "How to work with dates and times as file formats",
          "Text mining"
        ] },
      { code: "KM-03-KT08", n: "Data structures", w: 10,
        covers: [
          "Introduction to data structures",
          "Identifying data structures",
          "Assigning values to data structures",
          "Data manipulation"
        ] }
    ]
  },
  {
    id: "KM-04", code: "251201-005-00-KM-04",
    title: "Logical Thinking and Basic Calculations",
    nqf: 4, credits: 2, hours: 20,
    purpose: "The main focus of the learning in this knowledge module is to acquire mathematical thinking theory for solving problems and acquire basic maths knowledge for use during data analytics.",
    topics: [
      { code: "KM-04-KT01", n: "Mathematical thinking skills for problem solving", w: 15,
        covers: [
          "Benefits of mathematics",
          "Mathematical thinking steps for solving problems"
        ] },
      { code: "KM-04-KT02", n: "Conversion between decimal and binary systems", w: 5,
        covers: [
          "Introduction to binary numbers",
          "Performing addition and subtraction of positive whole numbers in binary",
          "Binary arithmetic",
          "Use of BODMAS"
        ] },
      { code: "KM-04-KT03", n: "Expressing size and magnitude", w: 5,
        covers: [
          "Use scientific notation for small and large numbers",
          "Prefixes: From Giga to Pica (109 to 10 -12)",
          "Conversions: SI to Imperial: degrees F to degrees C"
        ] },
      { code: "KM-04-KT04", n: "Error in calculations", w: 10,
        covers: [
          "Rational and irrational numbers",
          "Explore repeating decimals and convert them to fraction form",
          "Symbols for irrational numbers",
          "Rounding prematurely in calculations",
          "Accuracy",
          "Final value of a calculation expressed in terms of the required unit"
        ] },
      { code: "KM-04-KT05", n: "Cartesian coordinate system", w: 10,
        covers: [
          "Definition",
          "Terminology",
          "The coordinate plane",
          "Intersecting x- and y-axes",
          "Four quadrants",
          "Naming using Roman numerals",
          "Use and application – creating graphs and maps"
        ] },
      { code: "KM-04-KT06", n: "Pythagorean theorem for finding the distance between two points", w: 5,
        covers: [
          "Definition",
          "Terminology",
          "Purpose",
          "Determine the distance between two points on the Cartesian grid"
        ] },
      { code: "KM-04-KT07", n: "Operator precedence", w: 10,
        covers: [
          "Definition and terminology",
          "Purpose and use",
          "Order of operations (left to right)",
          "Apply precedence",
          "Use of PEDMAS in programming"
        ] },
      { code: "KM-04-KT08", n: "Integer division", w: 10,
        covers: [
          "Definition and terminology",
          "Purpose and use",
          "Rules on how to divide integers",
          "Names and symbols for integer division",
          "Software for integer division"
        ] },
      { code: "KM-04-KT09", n: "Modulus", w: 10,
        covers: [
          "Definition and terminology",
          "Purpose and use",
          "Modulus abbreviated as “mod”",
          "% application",
          "(==) application",
          "Modulus in programming"
        ] },
      { code: "KM-04-KT10", n: "Increments", w: 10,
        covers: [
          "Definition and terminology",
          "Purpose and use",
          "Increment a variable",
          "compound assignment operator",
          "Increments in programming"
        ] },
      { code: "KM-04-KT11", n: "Mixing types", w: 10,
        covers: [
          "Definition and terminology",
          "Purpose and use",
          "Apply an operator",
          "Combination with the order of operations",
          "The order in which an expression is written",
          "How a computer would evaluate some expressions that combine types"
        ] }
    ]
  },
  {
    id: "KM-05", code: "251201-005-00-KM-05",
    title: "Computing Theory",
    nqf: 4, credits: 2, hours: 20,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of programming as creating a set of instructions to a computer on how to perform a task using coding and programming languages.",
    topics: [
      { code: "KM-05-KT01", n: "Introduction to programming languages", w: 30,
        covers: [
          "Concepts, principles and terminology",
          "Developing structured and creative thinking skills through programming",
          "The logic of programming",
          "Choosing a programming language",
          "Syntax",
          "Learning code",
          "Installation and set-up"
        ] },
      { code: "KM-05-KT02", n: "Programming basics", w: 40,
        covers: [
          "Programming environment",
          "Algorithms",
          "Data types",
          "Variables",
          "Keywords",
          "Logical and arithmetical operators",
          "Logical operations: if-statements, where-statements, If else conditions",
          "Loops",
          "Numbers, characters and arrays",
          "Functions",
          "Input and output operations"
        ] },
      { code: "KM-05-KT03", n: "Software applications", w: 30,
        covers: [
          "Basic programming knowledge on HTML, JavaScript (or any scripting language)",
          "Software development, e.g. C#, C++, Java, .NET, Python, VB",
          "Databases (SQL)",
          "Web development technologies",
          "AI and Machine Learning concept and principles",
          "Project management methodology (e.g. Agile)",
          "Tools and technologies including at least one: including SQL and a minimum of one"
        ] }
    ]
  },
  {
    id: "KM-06", code: "251201-005-00-KM-06",
    title: "Software Development with HTML5, Opensource Frameworks and Libraries",
    nqf: 5, credits: 16, hours: 160,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of these topics: Core Programming, Object-Oriented Programming, General Software Development, Web Applications, Desktop Applications, Databases, Manage the Application Life Cycle, Build the User Interface by Using HTML5, Format the User Interface by Using CSS, and Code by Using JavaScript.",
    topics: [
      { code: "KM-06-KT01", n: "Core programming", w: 10,
        covers: [
          "Definition",
          "Core programming concepts",
          "Tools"
        ] },
      { code: "KM-06-KT02", n: "Object-Oriented Programming", w: 10,
        covers: [
          "Definition and concepts",
          "four principles : encapsulation, abstraction, inheritance, and polymorphism",
          "Objects and classes"
        ] },
      { code: "KM-06-KT03", n: "General Software Development", w: 15,
        covers: [
          "Programming logic",
          "Define and use variables",
          "Perform looping and branching",
          "Develop user interfaces",
          "Capture and validate user input",
          "Store data",
          "Create well-structured application"
        ] },
      { code: "KM-06-KT04", n: "Web applications", w: 10,
        covers: [
          "Aim and purpose",
          "Visual requirements",
          "Validation of input",
          "Defining input forms",
          "Providing feedback to users on incorrect input",
          "Tools to create web applications"
        ] },
      { code: "KM-06-KT05", n: "Applications development", w: 10,
        covers: [
          "Web applications",
          "Desktop applications",
          "Databases",
          "Application life cycle",
          "User interfaces"
        ] },
      { code: "KM-06-KT06", n: "HTML5", w: 15,
        covers: [
          "Overview",
          "User Interfaces",
          "Visually pleasing",
          "Text",
          "Graphics",
          "Media",
          "Organization",
          "Input",
          "Validation"
        ] },
      { code: "KM-06-KT07", n: "CSS", w: 15,
        covers: [
          "Overview",
          "Document object model (DOM)",
          "Content flow",
          "Positioning",
          "Styling",
          "Layouts",
          "Text flow",
          "Graphical interface",
          "Single aspects of presentation such as colour, text, box model, and animations"
        ] },
      { code: "KM-06-KT08", n: "JavaScript", w: 15,
        covers: [
          "Overview",
          "Document object model",
          "Using a browser",
          "Coding essentials",
          "Creating animations",
          "Working with graphics",
          "Accessing data touch interface",
          "Device and operating system resources"
        ] }
    ]
  },
  {
    id: "KM-07", code: "251201-005-00-KM-07",
    title: "UML as Standard Modelling Language for Software and Systems Development",
    nqf: 5, credits: 4, hours: 40,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of the modelling tools, topics and theory and how modelling assist in focussing on, capturing, documenting and communicating a system design. The Unified Modelling Language (UML) is the standard modelling language for software and systems development.",
    topics: [
      { code: "KM-07-KT01", n: "Introduction", w: 5,
        covers: [
          "Modelling language overview",
          "UML 2.0",
          "Models and diagrams",
          "“Degrees” of UML",
          "Using UML in the software development process",
          "Views of your model"
        ] },
      { code: "KM-07-KT02", n: "Modelling requirements", w: 5,
        covers: [
          "System requirement capturing",
          "Use case relationships",
          "Use case overview diagrams"
        ] },
      { code: "KM-07-KT03", n: "Workflows: activity diagrams", w: 10,
        covers: [
          "Activity diagram essentials",
          "Activities and actions",
          "Decisions and merges",
          "Simultaneous multiple tasks",
          "Time events",
          "Calling other activities",
          "Objects",
          "Sending and receiving signals",
          "Starting an activity",
          "Ending activities and flows",
          "Partitions (or Swimlanes)",
          "Complex activity diagrams"
        ] },
      { code: "KM-07-KT04", n: "Classes and class diagrams", w: 5,
        covers: [
          "Definition of class",
          "Classes in UML",
          "Visibility",
          "Class state: attributes",
          "Class behaviours: operations",
          "Static parts of a class"
        ] },
      { code: "KM-07-KT05", n: "Advanced class diagrams", w: 5,
        covers: [
          "Class relationships",
          "Constrains",
          "Abstract classes",
          "Interfaces",
          "Templates"
        ] },
      { code: "KM-07-KT06", n: "Object diagrams", w: 5,
        covers: [
          "Object instances",
          "Links",
          "Binding class templates"
        ] },
      { code: "KM-07-KT07", n: "Sequence diagrams", w: 10,
        covers: [
          "Participants in a sequence diagram",
          "Time",
          "Events, signals and messages",
          "Activation bars",
          "Nested messages",
          "Message arrows",
          "Use cases and sequence diagrams",
          "Managing complex interactions with sequence fragments"
        ] },
      { code: "KM-07-KT08", n: "Communication diagrams", w: 5,
        covers: [
          "Participants, links and messages",
          "Fleshing out an interaction with a communication diagram",
          "Communication diagrams versus sequence diagrams"
        ] },
      { code: "KM-07-KT09", n: "Timing diagrams", w: 15,
        covers: [
          "Interaction timing",
          "Appearance of timing diagrams",
          "Building a timing diagram from a sequence diagram",
          "Applying participants to a timing diagram",
          "States",
          "Time",
          "A participant’s state-line",
          "Events and messages",
          "Timing constraints",
          "Organizing participants on a timing diagram",
          "An alternative notation"
        ] },
      { code: "KM-07-KT10", n: "Interaction overview diagrams", w: 5,
        covers: [
          "The parts of an interaction overview diagram",
          "Modelling a use case using an interaction overview"
        ] },
      { code: "KM-07-KT11", n: "Classes internal structure: composite structures", w: 5,
        covers: [
          "Internal structure",
          "Using a class",
          "Patterns with collaborations"
        ] },
      { code: "KM-07-KT12", n: "Component diagrams", w: 10,
        covers: [
          "Definition of a component",
          "A basic component in UML",
          "Provided and required interfaces of a component",
          "Showing components working together",
          "Classes that realize a component",
          "Ports and internal structure",
          "Black-box and White-box component views",
          "Managing and reusing system parts"
        ] },
      { code: "KM-07-KT13", n: "Packages", w: 5,
        covers: [
          "Packages",
          "Namespaces and classes referring to each other",
          "Element visibility",
          "Package dependency",
          "Importing and accessing packages",
          "Managing package dependencies",
          "Organize model and use cases"
        ] },
      { code: "KM-07-KT14", n: "State machine diagrams", w: 5,
        covers: [
          "Essentials",
          "States",
          "Transitions",
          "States in software",
          "Advanced state behaviours",
          "Composite states",
          "Advanced pseudo states",
          "Signals",
          "Protocol state machines",
          "Modelling an object’s state"
        ] },
      { code: "KM-07-KT15", n: "Deployed diagrams", w: 5,
        covers: [
          "Deploying a simple system",
          "Deployment software: artefacts",
          "Nodes",
          "Hardware and execution environment nodes",
          "Communication between nodes",
          "Deployment specifications",
          "When to use deployment diagram"
        ] }
    ]
  },
  {
    id: "KM-08", code: "251201-005-00-KM-08",
    title: "Obtaining, Querying, Manipulating and Presenting Data with and without MVC",
    nqf: 5, credits: 6, hours: 60,
    purpose: "The main focus of the learning in this knowledge module is to acquire general knowledge and understanding of the principles and methods to obtain, query, manipulate and present data with and without MVC.",
    topics: [
      { code: "KM-08-KT01", n: "Understanding core database concepts", w: 10,
        covers: [
          "Flat-type databases",
          "Hierarchical databases",
          "Relational databases",
          "Database fundamentals",
          "Relational database concepts",
          "The SQL server management studio interface",
          "Data Manipulation Language (DML)",
          "Data Definition Language (DDL)"
        ] },
      { code: "KM-08-KT02", n: "Creating database", w: 10,
        covers: [
          "Defining data types",
          "Built-in data types",
          "Exact numeric data types",
          "Approximate numeric data types",
          "Creating and using tables",
          "Creating views",
          "Creating stored procedures",
          "SQL Injections"
        ] },
      { code: "KM-08-KT03", n: "Manipulating data", w: 25,
        covers: [
          "Using Queries to Select Data",
          "Combining Conditions",
          "Using the BETWEEN Clause",
          "Using the NOT Clause",
          "Using the UNION Clause",
          "Using the EXCEPT and INTERSECT Clauses",
          "Using the JOIN Clause",
          "Using Queries to Insert Data",
          "Updating Data and Databases: Using the UPDATE Statement",
          "Deleting Data",
          "Using the DELETE Statement",
          "Truncating a Table with TRUNCATE TABLE",
          "Deleting a Table with DROP TABLE",
          "Using Referential Integrity"
        ] },
      { code: "KM-08-KT04", n: "Understanding Data", w: 27,
        covers: [
          "Normalizing a database",
          "Understanding primary, foreign, and composite keys",
          "Understanding clustered and non-clustered"
        ] },
      { code: "KM-08-KT05", n: "Administering a database", w: 28,
        covers: [
          "Securing databases",
          "Backing up and restoring databases"
        ] }
    ]
  },
  {
    id: "KM-09", code: "251201-005-00-KM-09",
    title: "Software Development Life Cycle, Programming Languages, Algorithms and Security",
    nqf: 5, credits: 3, hours: 30,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of the principles and techniques applied during the SDLC, use of programming languages, design and development of algorithms and the importance of security during the development of software solutions.",
    topics: [
      { code: "KM-09-KT01", n: "Introduction to software development", w: 5,
        covers: [
          "History, evolution and future",
          "The information age",
          "Socio economic impact of IT",
          "The role and contribution of the software developer",
          "Software development",
          "Career opportunities and career progression"
        ] },
      { code: "KM-09-KT02", n: "Software Development Life Cycle", w: 5,
        covers: [
          "Concept, definitions and terminology",
          "Underlying principles",
          "Estimations and planning",
          "Purpose",
          "Overview and explanation of phases",
          "Version control: importance, methods and tools"
        ] },
      { code: "KM-09-KT03", n: "The need for a solution", w: 5,
        covers: [
          "Stakeholders",
          "What is a solution?",
          "Main elements of a successful solution",
          "Baseline information",
          "Manage change",
          "Scope of the project",
          "Use case"
        ] },
      { code: "KM-09-KT04", n: "Popular SDLC models", w: 5,
        covers: [
          "Waterfall model",
          "Incremental model",
          "V-model",
          "Agile model",
          "Spiral model",
          "Big bang model"
        ] },
      { code: "KM-09-KT05", n: "Phase 1: Requirement collection and analysis", w: 5,
        covers: [
          "Main focus and aim",
          "Role players and responsibilities",
          "Tools, procedures and checks",
          "Process and deliverables",
          "How it feeds into the next phase"
        ] },
      { code: "KM-09-KT06", n: "Phase 2: Feasibility study", w: 5,
        covers: [
          "Main focus and aim",
          "Role players and responsibilities",
          "Tools, procedures and checks",
          "Process and deliverables",
          "How it feeds into the next phase"
        ] },
      { code: "KM-09-KT07", n: "Phase 3: Design", w: 5,
        covers: [
          "Main focus and aim",
          "Role players and responsibilities",
          "Tools, procedures and checks",
          "Process and deliverables",
          "How it feeds into the next phase"
        ] },
      { code: "KM-09-KT08", n: "Phase 4: Coding", w: 5,
        covers: [
          "Main focus and aim",
          "Role players and responsibilities",
          "Tools, procedures and checks",
          "Process and deliverables",
          "How it feeds into the next phase"
        ] },
      { code: "KM-09-KT09", n: "Phase 5: Testing", w: 5,
        covers: [
          "Main focus and aim",
          "Role players and responsibilities",
          "Tools, procedures and checks",
          "Process and deliverables",
          "How it feeds into the next phase"
        ] },
      { code: "KM-09-KT10", n: "Phase 6: Installation/Deployment", w: 5,
        covers: [
          "Main focus and aim",
          "Role players and responsibilities",
          "Tools, procedures and checks",
          "Process and deliverables",
          "How it feeds into the next phase"
        ] },
      { code: "KM-09-KT11", n: "Phase 7: Maintenance", w: 5,
        covers: [
          "Main focus and aim",
          "Role players and responsibilities",
          "Tools, procedures and checks",
          "Process and deliverables",
          "How it feeds into the next phase"
        ] },
      { code: "KM-09-KT12", n: "Software development logging and commenting", w: 5,
        covers: [
          "Commenting techniques",
          "Software logging",
          "Importance and benefits of software logging",
          "Logging best practices",
          "Logging frameworks",
          "Procedures and type of information",
          "Using metrics",
          "Lessons learnt"
        ] },
      { code: "KM-09-KT13", n: "Programming and programming languages", w: 10,
        covers: [
          "Five basic programming elements",
          "Pseudo codes, mathematics and comments on concepts of programming languages",
          "Best suited for the purpose",
          "Back end programming languages",
          "Front end programming languages",
          "Middleware programming languages",
          "Best programming languages for mobile programming",
          "Best programming languages for computer programming",
          "Machine learning languages"
        ] },
      { code: "KM-09-KT14", n: "Coding", w: 5,
        covers: [
          "Code defined",
          "Difference between coding and programming",
          "The process of coding defined",
          "Script defined",
          "Basic coding skills",
          "Readability of source code"
        ] },
      { code: "KM-09-KT15", n: "Overview of algorithms in information technology", w: 5,
        covers: [
          "Definitions and basic concepts of algorithms in a programming perspective",
          "The difference between algorithms, pseudocode and programming languages",
          "Purpose",
          "Characteristics of good algorithms",
          "Advantages"
        ] },
      { code: "KM-09-KT16", n: "Algorithms", w: 5,
        covers: [
          "Best languages to write algorithms",
          "Types",
          "Where to use algorithms",
          "Writing algorithms",
          "Algorithms and data structures",
          "Algorithms and problem solving",
          "Algorithms and cryptography",
          "Mathematical formulas in texts",
          "Tools for representing algorithms"
        ] },
      { code: "KM-09-KT17", n: "Programming language concepts and terminology", w: 5,
        covers: [
          "Programming environment",
          "Basic language syntax rules",
          "Sequence, selection and iteration constructs",
          "Operators and expressions",
          "Variables, constants and datatypes",
          "Functions and procedures",
          "Conditions, nesting and loops",
          "Control structures",
          "Commands",
          "String functions",
          "Numbers, characters and arrays"
        ] },
      { code: "KM-09-KT18", n: "Security topics every software developer must know", w: 5,
        covers: [
          "Input validation",
          "Syntactic and semantic validation",
          "Blacklisting and whitelisting",
          "Output encoding",
          "Database access",
          "Implementing cryptography",
          "Secure error handling",
          "Third-party libraries",
          "Defense in depth: multiple layers of security"
        ] },
      { code: "KM-09-KT19", n: "Machine learning", w: 5,
        covers: [
          "Types of ML models",
          "ML algorithm classification",
          "Common ML algorithms",
          "ML workflow process (framework)",
          "Business benefits of ML"
        ] }
    ]
  },
  {
    id: "KM-10", code: "251201-005-00-KM-10",
    title: "Introduction to Software Developer Governance, Legislation and Ethics",
    nqf: 4, credits: 2, hours: 20,
    purpose: "The main focus of the learning in this knowledge module is to acquire general knowledge and understanding of the various legislations governing the workplace and their implications for the employer and employees. The learning of this module will also enable the learner to acquire an understanding of the principles of areas of performance management, business planning concepts, costing of products and concepts of general ethical behaviour and its impact in the workplace.",
    topics: [
      { code: "KM-10-KT01", n: "Governance", w: 20,
        covers: [
          "Definitions and the role of governance",
          "Rules, norms and actions are structured, sustained, regulated and held accountable",
          "Structures and processes and industry codes of practices",
          "Transparent, participatory, inclusive and responsive",
          "Domains of IT governance",
          "Compliance vs non-compliance"
        ] },
      { code: "KM-10-KT02", n: "Legislation governing workplaces", w: 15,
        covers: [
          "LRA",
          "POPI",
          "B-BBEE",
          "BCEA",
          "SDA",
          "Current trends"
        ] },
      { code: "KM-10-KT03", n: "Introduction to ethics and security", w: 5,
        covers: [
          "Principles and practices",
          "Concepts, definitions and terminology"
        ] },
      { code: "KM-10-KT04", n: "Ethics at work", w: 14,
        covers: [
          "Code of conduct and moral compass",
          "Components of ethical behaviour, including integrity, honesty, fair dealing, respecting",
          "Unwritten but expected behaviours, including reliability, accountability, time keeping,",
          "Lapses in ethical behaviour, including sexual harassment, racism, bullying, theft, abuse",
          "Conflicts of interest, including primary and secondary interests, the impact on individuals",
          "The need for ethical behaviour and the impact or consequences of lapses in ethical",
          "Copyright and plagiarism",
          "Intellectual property",
          "Spamming",
          "Contract management",
          "Software licensing",
          "Pricing"
        ] },
      { code: "KM-10-KT05", n: "Security", w: 15,
        covers: [
          "Risks, threats and vulnerabilities",
          "Mitigation tools and strategies",
          "Digital forensics",
          "Cloud",
          "Commercial law",
          "Cyber security",
          "New trends"
        ] },
      { code: "KM-10-KT06", n: "Performance management", w: 10,
        covers: [
          "Planning, organising and control",
          "Work flow",
          "Cost, waste",
          "Productivity, efficiency",
          "Housekeeping",
          "Risk, health, safety, environment and related systems",
          "Quality and quality systems",
          "Continual improvement"
        ] },
      { code: "KM-10-KT07", n: "Business planning", w: 7,
        covers: [
          "Business sustainability",
          "Concept of supply and demand",
          "Concept of profit, loss and breakeven",
          "Accountability and responsibility",
          "Competition",
          "Customers",
          "Contracts",
          "Budgets"
        ] },
      { code: "KM-10-KT08", n: "Costing of products", w: 7,
        covers: [
          "Input cost",
          "Overhead costs",
          "Direct labour cost",
          "Pricing a product (Under- or over-pricing)"
        ] },
      { code: "KM-10-KT09", n: "Resources", w: 7,
        covers: [
          "Human resources",
          "Financial resources",
          "Physical resources (infrastructure, machinery, equipment)",
          "Communication and information technology"
        ] }
    ]
  },
  {
    id: "KM-11", code: "251201-005-00-KM-11",
    title: "4IR and Future Skills",
    nqf: 4, credits: 2, hours: 20,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of the impact of 4IR on communities, individuals and businesses and important skills for future needs.",
    topics: [
      { code: "KM-11-KT01", n: "4 IR emerging trends", w: 10,
        covers: [
          "Artificial intelligence",
          "Cloud computing",
          "Cyber security",
          "Data science",
          "Internet of things",
          "Quality engineering automation",
          "Robotic processing automation",
          "Software programming",
          "Design thinking and innovation",
          "e-Waste"
        ] },
      { code: "KM-11-KT02", n: "Computing Knowledge", w: 7,
        covers: [
          "Introduction to programming language",
          "Programming basics",
          "Basic programming knowledge on HTML, JavaScript (or any scripting language)",
          "Software development, e.g. C#, C++, Java, .NET",
          "Databases (SQL or NoSQL)",
          "Web development technologies"
        ] },
      { code: "KM-11-KT03", n: "Future skills and competencies (4IR)", w: 10,
        covers: [
          "Disruptive thinking (encourage this) (application to their own environment)",
          "Continuously searching for ideas",
          "Think innovatively (analyse the current market and come up with solutions to the current",
          "Soft skills",
          "Programming languages",
          "Operating systems",
          "Open source",
          "Tools for a cloud environment (for configuration and management), tools for debugging,",
          "Familiarity with Office tools",
          "Leadership and people management skills"
        ] },
      { code: "KM-11-KT04", n: "4 IR trends affecting businesses", w: 10,
        covers: [
          "Afro-centric approach to African problems – taking the best from the existing products",
          "Using Google, Amazon and MS forms and tools to reduce development time (e.g. embed",
          "Business intelligence applications and availability of Big Data (collecting data, converting",
          "Collecting data on clients",
          "Insight into different markets",
          "Automated factories",
          "Exposure to the global world"
        ] },
      { code: "KM-11-KT05", n: "Interpersonal skills", w: 5,
        covers: [
          "Concept, definition and terminology",
          "Principles",
          "Attributes"
        ] },
      { code: "KM-11-KT06", n: "Intrapersonal skills", w: 5,
        covers: [
          "Concept, definition and terminology",
          "Principles",
          "Attributes"
        ] },
      { code: "KM-11-KT07", n: "Communication principles and methods", w: 5,
        covers: [
          "Concept, definition and terminology",
          "The different types and forms of communication and communication processes",
          "Communication methods",
          "Barriers to communication",
          "Communication network: Interdepartmental, Supply chain network, etc.",
          "Advantages of good communication",
          "Consequences of poor/no communication"
        ] },
      { code: "KM-11-KT08", n: "Written business communication", w: 7,
        covers: [
          "Business requirement specifications",
          "Types",
          "Conventions",
          "Schedules",
          "Reports, reporting protocols and methods",
          "Manuals and guidelines",
          "Work instructions/briefs",
          "Technical report writing",
          "Extracting information from written texts",
          "Policies aligned to standard (IEEE 829-2008 standards)"
        ] },
      { code: "KM-11-KT09", n: "Presentation skills", w: 7,
        covers: [
          "Concept, definition and terminology",
          "Types: visual, verbal, written",
          "Conventions",
          "Presenting options and solutions",
          "Presenting technical details",
          "Visualisation of business intelligence",
          "Suitable APIs and storytelling using the right tools to"
        ] },
      { code: "KM-11-KT10", n: "Teamwork in the workplace", w: 10,
        covers: [
          "Concept, definition and terminology",
          "Principles of teamwork",
          "Advantages of teamwork",
          "Team composition and members",
          "Roles, responsibilities and functions",
          "Team dynamics",
          "Common goals and collaboration",
          "Nature of multidisciplinary teams and teamwork",
          "Setting and achieving targets",
          "Collaboration tools (electronic)"
        ] },
      { code: "KM-11-KT11", n: "Committees and meetings", w: 5,
        covers: [
          "Procedures",
          "Agendas and minutes",
          "Roles and responsibilities",
          "WSP committees",
          "EE committees",
          "Safety and health committees",
          "Wellness committees"
        ] },
      { code: "KM-11-KT12", n: "Job descriptions and profiles", w: 5,
        covers: [
          "Purpose",
          "Job and person specification",
          "Content",
          "Alignment to performance standards"
        ] },
      { code: "KM-11-KT13", n: "Customers and stakeholders", w: 7,
        covers: [
          "Concept, definition and terminology",
          "Types of customers",
          "Customer profile",
          "Typical customer behaviour: including habits and mannerisms",
          "Difficult customers",
          "Customer care",
          "Stakeholder management and participation"
        ] },
      { code: "KM-11-KT14", n: "Customer service", w: 7,
        covers: [
          "Concept, definition and terminology",
          "Customer service principles",
          "Customer centeredness",
          "Handover and sign-off procedures and techniques",
          "Technical documentation",
          "Training in the use of the system"
        ] }
    ]
  },
  {
    id: "KM-12", code: "251201-005-00-KM-12",
    title: "Design Thinking Principles for Innovation",
    nqf: 4, credits: 1, hours: 10,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of design thinking principles and application in the workplace.",
    topics: [
      { code: "KM-12-KT01", n: "Introduction to design thinking", w: 15,
        covers: [
          "Philosophy",
          "Approach and concepts",
          "Definitions and terminology",
          "History"
        ] },
      { code: "KM-12-KT02", n: "The human element", w: 10,
        covers: [
          "Human centeredness",
          "Human participation"
        ] },
      { code: "KM-12-KT03", n: "Creativity", w: 20,
        covers: [
          "Creativity: is unleashing the potential of the mind to conceive new ideas",
          "Perceiving the world in new ways",
          "Find hidden patterns",
          "Make connections between seemingly unrelated phenomena",
          "Generate solutions",
          "Application in the workplace"
        ] },
      { code: "KM-12-KT04", n: "Innovation", w: 20,
        covers: [
          "Innovation: is the action of putting things into practical reality, despite challenges and",
          "Different innovations",
          "Main types of innovation",
          "What innovation means to business"
        ] },
      { code: "KM-12-KT05", n: "Design", w: 10,
        covers: [
          "Think outside the box",
          "Push beyond the obvious solutions",
          "Communication through shape and form"
        ] },
      { code: "KM-12-KT06", n: "Design thinking methodology", w: 10,
        covers: [
          "Design thinking phases",
          "Design thinking tools and techniques"
        ] },
      { code: "KM-12-KT07", n: "Application of design thinking", w: 15,
        covers: [
          "Application in software development",
          "Application in cyber security",
          "Business innovation",
          "Innovative problem solving"
        ] }
    ]
  }
  ];

  /* No links in this file. materials.js fills these in from the server for a
     learner who is enrolled and entitled to them. */
  MODULES.forEach(function (m) {
    m.docs = { guide: null, workbook: null, video: null };
  });

  /* Registered with the others, so pages can ask "which curriculum is this
     course" without naming files. See po-modules.js for the shape.

     ACCREDITED (20 Nov 2026, Sibusiso for Kgomotso: "the ones we have material
     for are the accredited, the ones without material are short courses").
     adminOnly stays true until the branded material is uploaded and checked;
     then it is listed. It is a flag for people reading this file: what
     actually keeps it off the catalogue is lib/learner.php. */
  window.ACADEMY_CURRICULA = window.ACADEMY_CURRICULA || {};
  window.ACADEMY_CURRICULA["software-developer"] = {
    slug: "software-developer",
    title: "Occupational Certificate: Software Developer",
    fullTitle: "Occupational Certificate: Software Developer",
    short: "Software Developer",
    adminOnly: true,
    saqa: "118707",
    curriculum: "251201-005-00",
    nqf: 5,         // the Software Developer EISA specification gives NQF Level 5
    credits: 220,            // the whole qualification
    knowledgeCredits: 66,    // what is studied on the site
    workbooks: true,         // this pack has a learner workbook per knowledge module
    plan: null,              // no study planner written for it yet
    modules: MODULES,
    byId: MODULES.reduce(function (a, m) { a[m.id] = m; return a; }, {})
  };

  window.SD_MODULES = MODULES;
  window.SD_MODULE_BY_ID = window.ACADEMY_CURRICULA["software-developer"].byId;

  /* ---- self-check: the registered figures, verified in the browser ---- */
  (function () {
    const credits = MODULES.reduce(function (a, m) { return a + m.credits; }, 0);
    if (credits !== 66) console.error('sd-modules.js: knowledge credits are ' + credits + ', the curriculum says 66');
    if (MODULES.length !== 12) console.error('sd-modules.js: ' + MODULES.length + ' modules, the curriculum says 12');
    MODULES.forEach(function (m) {
      const w = m.topics.reduce(function (a, t) { return a + t.w; }, 0);
      if (w !== 100) console.error('sd-modules.js: ' + m.id + ' topic weightings sum to ' + w + '%, not 100%');
    });
  })();

})();
