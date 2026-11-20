/* ---- AI Software Developer: the twelve knowledge modules --------------------
   Generated from the provider's QCTO curriculum document (curriculum
   251201-002-00, SAQA 118792) on 20 Nov 2026: SECTION 3A, which gives the
   modules, their credits and NQF levels, each topic and its weighting, and the
   topic elements. These are the REGISTERED figures, not marketing copy, and
   must not be hand-edited. Every module's weightings sum to 100 and the
   credits sum to 86; both are asserted at the bottom of this file, so a bad
   edit fails loudly.

   The qualification is 209 credits in total: 86 knowledge, 63 practical skill
   (PM-01 to PM-11, listed in lib/submissions.php) and 60 work experience.
   Only the knowledge modules are studied on the site.

   KM-06 really is a single topic weighted 100% ("AI vs ML vs DL"); that is
   the curriculum, not a parsing gap. Topics are numbered by the section they
   sit under, never by the code printed in the heading.

   NOT the same thing as 'ai-software-development', the short professional
   programme in the catalogue. They are told apart by slug everywhere.

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
    id: "KM-01", code: "251201-002-00-KM-01",
    title: "Overview of Artificial Intelligence",
    nqf: 4, credits: 2, hours: 20,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of artificial Intelligence, its definition and future, as well as the purpose and contribution of AI to society and business.",
    topics: [
      { code: "KM-01-KT01", n: "Introduction to AI", w: 40,
        covers: [
          "Evolution of Artificial Intelligence (AI)",
          "Defining AI",
          "Realistic and unrealistic AI",
          "Fields related to AI",
          "Taxonomy of AI",
          "Strong vs weak AI",
          "Why is AI important",
          "Limitation of AI"
        ] },
      { code: "KM-01-KT02", n: "Background to AI", w: 20,
        covers: [
          "AI applications",
          "AI making applications friendlier"
        ] },
      { code: "KM-01-KT03", n: "Strategic advantage of AI in business", w: 40,
        covers: [
          "Introduction to the 4th Industrial Revolution (4IR)",
          "4IR vs AI",
          "Strategic advantage of AI in Business",
          "AI technology supporting business",
          "AI in production or manufacturing",
          "AI in the medical fields",
          "AI in agriculture",
          "AI in the finance industry",
          "AI in engineering",
          "AI improving human interaction"
        ] }
    ]
  },
  {
    id: "KM-02", code: "251201-002-00-KM-02",
    title: "Introduction to Mathematics and Statistics",
    nqf: 4, credits: 10, hours: 100,
    purpose: "The main focus of the learning in this knowledge module is to revise and acquire mathematical and statistical theory to successfully understand and interpret actions of Artificial Intelligence, Machine Learning, Deep Learning and Data Analytics.",
    topics: [
      { code: "KM-02-KT01", n: "Basic Mathematics", w: 20,
        covers: [
          "Operator precedence",
          "Integer division",
          "Modulus",
          "Mixing Types"
        ] },
      { code: "KM-02-KT02", n: "Linear Algebra", w: 10,
        covers: [
          "Linear transformation",
          "Vectors",
          "Matrices",
          "Matrix operations",
          "Special functions in linear algebra"
        ] },
      { code: "KM-02-KT03", n: "Conversion between decimal and binary systems", w: 5,
        covers: [
          "Introduction to binary numbers",
          "Perform addition and subtraction of positive whole numbers in binary",
          "Binary arithmetic"
        ] },
      { code: "KM-02-KT04", n: "Expressing size and magnitude", w: 5,
        covers: [
          "Use scientific notation for small and large numbers",
          "Prefixes",
          "SI to Imperial",
          "Degrees F to degrees C"
        ] },
      { code: "KM-02-KT05", n: "Error in calculations", w: 5,
        covers: [
          "Rational and irrational numbers",
          "Explore repeating decimals and convert them to fraction",
          "Symbols for irrational numbers",
          "Rounding prematurely in calculations",
          "Accuracy in calculations",
          "Final value of a calculation expressed in terms of the required unit",
          "When PEMDAS fail"
        ] },
      { code: "KM-02-KT06", n: "Cartesian coordinate system", w: 5,
        covers: [
          "Definition",
          "Terminology",
          "The coordinate plane",
          "Naming using Roman numerals",
          "Use an application to create graphs and maps"
        ] },
      { code: "KM-02-KT07", n: "Pythagorean theorem", w: 5,
        covers: [
          "What is a theorem?",
          "Definition of Pythagorean theorem",
          "Finding the distance between two points",
          "Terminology",
          "Purpose",
          "Determine the distance between two points on the Cartesian grid"
        ] },
      { code: "KM-02-KT08", n: "Increments", w: 5,
        covers: [
          "Definition and terminology",
          "Purpose and use of increments",
          "Increment a variable",
          "Compound assignment operator",
          "Increments in programming"
        ] },
      { code: "KM-02-KT09", n: "Calculus", w: 5,
        covers: [
          "Calculus essentials",
          "Derivatives"
        ] },
      { code: "KM-02-KT10", n: "Probabilities", w: 5,
        covers: [
          "Definition and terminology",
          "Probability essentials",
          "Probability basics and notations",
          "Probabilities and odds",
          "Probability",
          "The Bayes rule",
          "What are parameters",
          "Estimating parameters",
          "Conditional probability",
          "Essential probability theorems for ML"
        ] },
      { code: "KM-02-KT11", n: "Statistics", w: 20,
        covers: [
          "Why is statistics important in AI?",
          "Introduction to statistics",
          "Qualitative and quantitative research",
          "Statistics and machine learning",
          "Gaussian distribution and descriptive statistics",
          "Correlation between variables",
          "Statistical Hypothesis Tests",
          "Estimation statistics",
          "Types of intervals",
          "Nonparametric statistics"
        ] },
      { code: "KM-02-KT12", n: "Bayes’ Theorem", w: 10,
        covers: [
          "Bayes’ theorem"
        ] }
    ]
  },
  {
    id: "KM-03", code: "251201-002-00-KM-03",
    title: "Analytical Thinking and Problem Solving",
    nqf: 4, credits: 3, hours: 30,
    purpose: "The main focus of the learning in this knowledge module is to provide the learner with an opportunity to acquire theory for formulating a problem and applying knowledge to design and create a solution for such a problem.",
    topics: [
      { code: "KM-03-KT01", n: "Introduction to analytical thinking", w: 40,
        covers: [
          "Analytical thinking",
          "Types of thinking",
          "Define analytical thinking",
          "Analytical thinking skills"
        ] },
      { code: "KM-03-KT02", n: "Problem solving and critical thinking", w: 40,
        covers: [
          "Root cause analysis (RCA)",
          "Decision tree analysis"
        ] },
      { code: "KM-03-KT03", n: "AI problem solving", w: 20,
        covers: [
          "Formulate a real-world problem",
          "Search data",
          "Solve problems with AI",
          "Formulate a simple game tree"
        ] }
    ]
  },
  {
    id: "KM-04", code: "251201-002-00-KM-04",
    title: "Data, Databases and Data Visualisation",
    nqf: 4, credits: 8, hours: 80,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of data and databases and giving meaning to data through data processing, analysis and visualisation.",
    topics: [
      { code: "KM-04-KT01", n: "Introduction to data", w: 20,
        covers: [
          "Value of data",
          "Data analysis for AI: Importance of analysis",
          "Data sourcing",
          "Refining data",
          "Flaws in data",
          "Limits of data acquisition",
          "Data",
          "Wrangling",
          "Approaches in data analysis"
        ] },
      { code: "KM-04-KT02", n: "Data and spreadsheets", w: 20,
        covers: [
          "Use spreadsheets to analyse and visualise data"
        ] },
      { code: "KM-04-KT03", n: "Data analytics", w: 10,
        covers: [
          "Types of data",
          "Operators",
          "Conditional statements",
          "Inference and modelling"
        ] },
      { code: "KM-04-KT04", n: "Introduction to databases", w: 15,
        covers: [
          "What is a Database",
          "Data Storage",
          "Relational database design",
          "Import and export data",
          "Design and create queries",
          "Data driven solutions"
        ] },
      { code: "KM-04-KT05", n: "Data mining", w: 15,
        covers: [
          "What is data mining?",
          "Data mining implementation process",
          "Data mining techniques",
          "Challenges of data mine implementations",
          "Data mining tools",
          "Advantages and disadvantages of data mining",
          "Application of data mining"
        ] },
      { code: "KM-04-KT06", n: "Structured query language (SQL)", w: 5,
        covers: [
          "SQL programming language",
          "SQL code constructs to perform database transactions",
          "Storing, retrieving, managing or manipulating the data inside a relational database"
        ] },
      { code: "KM-04-KT07", n: "Visualising data with AI tools", w: 5,
        covers: [
          "Introduction to data visualization"
        ] },
      { code: "KM-04-KT08", n: "Data security", w: 10,
        covers: [
          "Definition",
          "Purpose of protecting data",
          "Process for protecting data",
          "Unauthorised access",
          "Data corruption",
          "Data security solutions"
        ] }
    ]
  },
  {
    id: "KM-05", code: "251201-002-00-KM-05",
    title: "Computing Theory",
    nqf: 4, credits: 8, hours: 80,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of programming as creating a set of instructions for a computer on how to perform a task, using coding and programming languages.",
    topics: [
      { code: "KM-05-KT01", n: "Introduction to programming languages", w: 25,
        covers: [
          "Concepts, principles and terminology",
          "Developing structured and creative thinking skills through programming",
          "The logic of programming",
          "Types of programming languages",
          "Choosing a programming language",
          "Top 5 AI programming languages"
        ] },
      { code: "KM-05-KT02", n: "Introduction to algorithms", w: 25,
        covers: [
          "Advantages and disadvantages of AI programming languages"
        ] },
      { code: "KM-05-KT03", n: "Programming basics", w: 20,
        covers: [
          "Programming environment",
          "Algorithms",
          "Data types",
          "Variables",
          "Keywords",
          "Logical and arithmetical operators",
          "Logical operations: if-statements, where-statements, If-else conditions",
          "Loops",
          "Numbers, characters and arrays",
          "Functions",
          "Input and output operations"
        ] },
      { code: "KM-05-KT04", n: "Solution development", w: 15,
        covers: [
          "Software development principles",
          "Computational thinking"
        ] },
      { code: "KM-05-KT05", n: "Introduction to Python", w: 15,
        covers: [
          "Python programming",
          "Installing Python",
          "Python Programming"
        ] }
    ]
  },
  {
    id: "KM-06", code: "251201-002-00-KM-06",
    title: "Introduction to Artificial Intelligence, Machine Learning, Deep Learning",
    nqf: 4, credits: 5, hours: 50,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of the relationship between Artificial Intelligence, Machine Learning and Deep Learning, as well as the application of such systems to create a set of instructions to perform a programming task.",
    topics: [
      { code: "KM-06-KT01", n: "Artificial Intelligence (AI) vs Machine Learning (ML) vs Deep Learning (DL)", w: 100,
        covers: [
          "Artificial Intelligence Systems",
          "Introduction to Machine Learning (ML)",
          "Introduction to Deep Learning (DL)"
        ] }
    ]
  },
  {
    id: "KM-07", code: "251201-002-00-KM-07",
    title: "Artificial Intelligence",
    nqf: 5, credits: 12, hours: 120,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of the relationship between Artificial Intelligence, Machine Learning and Deep Learning, as well as the application of AI to create a set of instructions to perform a programming task.",
    topics: [
      { code: "KM-07-KT01", n: "AI frameworks", w: 50,
        covers: [
          "Introduction to AI frameworks",
          "Advantages and disadvantages of frameworks",
          "Fundamentals of each framework",
          "Demonstration of each framework"
        ] },
      { code: "KM-07-KT02", n: "Using AI for data scraping", w: 50,
        covers: [
          "Concept and definition",
          "Purpose of data scraping",
          "Data scraping tools",
          "Legal issues",
          "Web scraping procedure",
          "Libraries used for web scraping"
        ] }
    ]
  },
  {
    id: "KM-08", code: "251201-002-00-KM-08",
    title: "Machine Learning",
    nqf: 5, credits: 16, hours: 160,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of the relationship between Artificial Intelligence, Machine Learning and Deep Learning, as well as the application of ML to create a set of instructions to perform a programming task.",
    topics: [
      { code: "KM-08-KT01", n: "Types of ML models", w: 20,
        covers: [
          "Binary classification model",
          "Multiclass classification model",
          "Regression classification model",
          "ML features and labels",
          "ML Advantages and disadvantages"
        ] },
      { code: "KM-08-KT02", n: "ML algorithm classification", w: 20,
        covers: [
          "Supervised Learning",
          "Unsupervised Learning",
          "Reinforcement Learning"
        ] },
      { code: "KM-08-KT03", n: "Common ML algorithms", w: 30,
        covers: [
          "Supervised Learning",
          "Unsupervised Learning",
          "Reinforcement Learning",
          "Reinforcement Learning"
        ] },
      { code: "KM-08-KT04", n: "ML Workflow Process (Framework)", w: 10,
        covers: [
          "Data Collection",
          "Data Preparation",
          "Choose a Model",
          "Train the Model",
          "Evaluate the Model",
          "Parameter Tuning",
          "Make Predictions"
        ] },
      { code: "KM-08-KT05", n: "Business benefits of ML", w: 20,
        covers: [
          "Real-time business decision making",
          "Eliminating manual tasks",
          "Enhancing security and network performance",
          "Improved business models and services",
          "Reducing operating expense",
          "Other"
        ] }
    ]
  },
  {
    id: "KM-09", code: "251201-002-00-KM-09",
    title: "Deep Learning",
    nqf: 5, credits: 16, hours: 160,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of the relationship between Artificial Intelligence, Machine Learning and Deep Learning, as well as the application of Deep Learning to create a set of instructions to perform a programming task using a Deep Learning tool.",
    topics: [
      { code: "KM-09-KT01", n: "Deep Learning (DL)", w: 40,
        covers: [
          "DL neural network architecture",
          "DL fundamental network architectures",
          "Input and output nodes",
          "Activation functions in DL",
          "Activation function in TensorFlow",
          "Building a simple DL Network",
          "Tuning a DL Network"
        ] },
      { code: "KM-09-KT02", n: "Advance Python for Deep Learning", w: 25,
        covers: [
          "Python programming primer"
        ] },
      { code: "KM-09-KT03", n: "TensorFlow and Keras for Deep Learning", w: 35,
        covers: [
          "TensorFlow 2.0 Basics",
          "Keras (TensorFlow 2.0 Built-in API) Overview"
        ] }
    ]
  },
  {
    id: "KM-10", code: "251201-002-00-KM-10",
    title: "Introduction to Governance, Legislation and Ethics",
    nqf: 4, credits: 1, hours: 10,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of the various legislations governing the workplace and their implication for the employer and employees. The learning of this module will also enable the learner to acquire an understanding of the principles of areas of performance management, business planning concepts, costing of products and concepts of general ethical behaviour and its impact in the workplace.",
    topics: [
      { code: "KM-10-KT01", n: "Governance", w: 20,
        covers: [
          "Definitions and the role of governance",
          "Rules, norms and actions that are structured, sustained, regulated and held accountable",
          "Structures and processes and industry codes of practice",
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
          "Components of ethical behaviour, including integrity, honesty, fair dealing and respecting",
          "Unwritten but expected behaviours, including reliability, accountability, time keeping and",
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
    id: "KM-11", code: "251201-002-00-KM-11",
    title: "Fundamentals of Design Thinking and Innovation",
    nqf: 4, credits: 1, hours: 10,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of the design thinking principles and applications in the workplace.",
    topics: [
      { code: "KM-11-KT01", n: "Introduction to design thinking", w: 15,
        covers: [
          "Philosophy",
          "Approach and concepts",
          "Definitions and terminology",
          "History"
        ] },
      { code: "KM-11-KT02", n: "The human element", w: 10,
        covers: [
          "Human centeredness",
          "Human participation"
        ] },
      { code: "KM-11-KT03", n: "Creativity", w: 20,
        covers: [
          "Creativity: is unleashing the potential of the mind to conceive new ideas",
          "Perceiving the world in new ways",
          "Find hidden patterns",
          "Make connections between seemingly unrelated phenomena",
          "Generate solutions",
          "Application in the workplace"
        ] },
      { code: "KM-11-KT04", n: "Innovation", w: 20,
        covers: [
          "Innovation: is the action of putting things into practical reality, despite challenges and",
          "Different innovations",
          "Main types of innovation",
          "What innovation means to business"
        ] },
      { code: "KM-11-KT05", n: "Design", w: 10,
        covers: [
          "Think outside the box",
          "Push beyond the obvious solutions",
          "Communication through shape and form"
        ] },
      { code: "KM-11-KT06", n: "Design thinking methodology", w: 10,
        covers: [
          "Design thinking phases",
          "Design thinking tools and techniques"
        ] },
      { code: "KM-11-KT07", n: "Application of design thinking", w: 15,
        covers: [
          "Application in software development",
          "Application in cyber security",
          "Business innovation",
          "Innovative problem solving"
        ] }
    ]
  },
  {
    id: "KM-12", code: "251201-002-00-KM-12",
    title: "4IR and Future Skills",
    nqf: 4, credits: 4, hours: 40,
    purpose: "The main focus of the learning in this knowledge module is to build an understanding of the impact of 4IR on communities, individuals and businesses as well as important skills for future needs.",
    topics: [
      { code: "KM-12-KT01", n: "4 IR emerging trends", w: 10,
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
      { code: "KM-12-KT02", n: "Computing Knowledge", w: 7,
        covers: [
          "Introduction to programming language",
          "Programming basics",
          "Basic programming knowledge on HTML, JavaScript (or any scripting language)",
          "Software development, e.g., C#, C++, Java, .NET",
          "Databases (SQL or NoSQL)",
          "Web development technologies"
        ] },
      { code: "KM-12-KT03", n: "Future skills and competencies (4IR)", w: 10,
        covers: [
          "Disruptive thinking (encourage this) (application to their own environment)",
          "Continuously searching for ideas",
          "Thinking innovatively (analyse the current market and come up with solutions to the",
          "Soft skills",
          "Programming languages",
          "Operating systems",
          "Open source",
          "Tools for a cloud environment (for configuration and management), tools for debugging,",
          "Familiarity with Office tools",
          "Leadership and people management skills"
        ] },
      { code: "KM-12-KT04", n: "4 IR trends affecting businesses", w: 10,
        covers: [
          "Afro-centric approach to African problems – taking the best from the existing products",
          "Using Google, Amazon and MS forms and tools to reduce development time (e.g.,",
          "Business intelligence applications and availability of Big Data (collecting data, converting",
          "Collecting data on clients",
          "Insight into different markets",
          "Automated factories",
          "Exposure to the global world"
        ] },
      { code: "KM-12-KT05", n: "Interpersonal skills", w: 5,
        covers: [
          "Concept, definition and terminology",
          "Principles",
          "Attributes"
        ] },
      { code: "KM-12-KT06", n: "Intrapersonal skills", w: 5,
        covers: [
          "Concept, definition and terminology",
          "Principles",
          "Attributes"
        ] },
      { code: "KM-12-KT07", n: "Communication principles and methods", w: 5,
        covers: [
          "Concept, definition and terminology",
          "The different types and forms of communication and communication processes",
          "Communication methods",
          "Barriers to communication",
          "Communication network: Interdepartmental, Supply chain network, etc.",
          "Advantages of good communication",
          "Consequences of poor/no communication"
        ] },
      { code: "KM-12-KT08", n: "Written business communication", w: 7,
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
      { code: "KM-12-KT09", n: "Presentation skills", w: 7,
        covers: [
          "Concept, definition and terminology",
          "Types: visual, verbal, written",
          "Conventions",
          "Presenting options and solutions",
          "Presenting technical details",
          "Visualisation of business intelligence",
          "Suitable APIs and storytelling using the right tools to"
        ] },
      { code: "KM-12-KT10", n: "Teamwork in the workplace", w: 10,
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
      { code: "KM-12-KT11", n: "Committees and meetings", w: 5,
        covers: [
          "Procedures",
          "Agendas and minutes",
          "Roles and responsibilities",
          "WSP committees",
          "EE committees",
          "Safety and health committees",
          "Wellness committees"
        ] },
      { code: "KM-12-KT12", n: "Job descriptions and profiles", w: 5,
        covers: [
          "Purpose",
          "Job and person specification",
          "Content",
          "Alignment to performance standards"
        ] },
      { code: "KM-12-KT13", n: "Customers and stakeholders", w: 7,
        covers: [
          "Concept, definition and terminology",
          "Types of customers",
          "Customer profile",
          "Typical customer behaviour: including habits and mannerisms",
          "Difficult customers",
          "Customer care",
          "Stakeholder management and participation"
        ] },
      { code: "KM-12-KT14", n: "Customer service", w: 7,
        covers: [
          "Concept, definition and terminology",
          "Customer service principles",
          "Customer centeredness",
          "Handover and sign-off procedures and techniques",
          "Technical documentation",
          "Training in the use of the system"
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
  window.ACADEMY_CURRICULA["ai-software-developer"] = {
    slug: "ai-software-developer",
    title: "Occupational Certificate: Artificial Intelligence Software Developer",
    fullTitle: "Occupational Certificate: Artificial Intelligence Software Developer",
    short: "AI Software Developer",
    adminOnly: true,
    saqa: "118792",
    curriculum: "251201-002-00",
    nqf: null,      // not stated in the pack (NQF 4 is the ENTRY requirement); confirm from SAQA
    credits: 209,            // the whole qualification
    knowledgeCredits: 86,    // what is studied on the site
    workbooks: true,         // this pack has a learner workbook per knowledge module
    plan: null,              // no study planner written for it yet
    modules: MODULES,
    byId: MODULES.reduce(function (a, m) { a[m.id] = m; return a; }, {})
  };

  window.AISD_MODULES = MODULES;
  window.AISD_MODULE_BY_ID = window.ACADEMY_CURRICULA["ai-software-developer"].byId;

  /* ---- self-check: the registered figures, verified in the browser ---- */
  (function () {
    const credits = MODULES.reduce(function (a, m) { return a + m.credits; }, 0);
    if (credits !== 86) console.error('aisd-modules.js: knowledge credits are ' + credits + ', the curriculum says 86');
    if (MODULES.length !== 12) console.error('aisd-modules.js: ' + MODULES.length + ' modules, the curriculum says 12');
    MODULES.forEach(function (m) {
      const w = m.topics.reduce(function (a, t) { return a + t.w; }, 0);
      if (w !== 100) console.error('aisd-modules.js: ' + m.id + ' topic weightings sum to ' + w + '%, not 100%');
    });
  })();

})();
