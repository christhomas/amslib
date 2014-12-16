<section>
	<h1>About Amslib</h1>

	<p>
		Back in 2004 I started to get involved with web development as I saw
		it an exciting new field that was uncharted and largely undeveloped,
		the scene I was part of in those days was OpenGL, C and C++, learning
		how Object Orientation, Encapsulation, Polymorphism and learning how
		to dynamically load code modules in the shape of DLL libraries into
		application code, from the very basic command line linkages and
		learning how to build more complex projects.</br>
		<br />
		What I created was something I called FusionEngine, which was a modular game
		engine with a dynamic load mechanism for all the code modules relating
		to sound, graphics and a configuration which would load the
		appropriate modules, using Polymorphism to hide the exact details of
		how the objects inside worked, I was able to create something I was
		very happy with, a system I could load multiple types of graphics
		renderer, OpenGL or Direct3D and render graphics without the game
		knowing the details.<br />
		<br />
		When it came to doing web development. I started out with was just some
		cool new CSS styles and a few very basic PHP methods and started to construct
		some Objects using my experience with C++ as it was all I needed to get
		the job done. Although this was a long way away from where we are now, I
		didn't even use .htaccess files, know how to write pretty urls or make
		MVC frameworks. Everything was plain index.php, about.php scripts and
		loading the files containing the common code manually.<br />
		<br />
		When I arrived in Barcelona in 2006, I worked for a company who wanted
		to make a Youtube clone as it was the year when Youtube became famous,
		it wasn't a very successful project since it was too large for a
		single person to do, too complex to handle and vastly under funded,
		like many projects. However I used this time to start to build
		something like I build with C++ and the Fusion Engine<br />
		<br />
		It wasn't until 2008 when I worked for a very popular hotel reservation
		platform as a lead developer that I fully understood why I had been
		doing it wrong in terms of advanced PHP frameworks and realised that
		CodeIgnitor had a very interesting pattern, of course it wasn't alone,
		but it was new to me. However I had some reservations about how it
		implemented some of the details, I believed that it didn't go far
		enough in attempting to do the work and shouldered the programmer with
		too much baggage, it was the right direction to go.<br />
		<br />
		Using this new knowledge, Amslib became a very rapidly developing platform
		that I was using for every one of my projects. I added new objects as
		new features became useful and I refactored old code into modules that
		could be loaded easily. A simple list of features that Amslib provides
		is probably quite familiar with some exceptions that I think make it
		very easy and valuable to use in any custom website.
	</p>
	<ul>
		<li>A core Amslib object which provides autoloaders for the entire
			framework, eliminating the horrible include/require mess, no
			freestanding functions</li>
		<li>An XML plugin/component system where you can set values in a
			package.xml to configure what resources the plugin would use, import
			or export (explained later). It's basically a manifest file the
			system loads and holds "instructions" for the plugin system to do
			things without using programming code</li>
		<li>The plugins/components can import/export information between each
			other, allowing them to configure deep into the plugin heirarchy
			values which are not accessible other than to manually edit the
			plugin. The idea is that each plugin is like a cell in your body and
			responsible for it's own affairs, it has it's own control structures
			which follow the same basic pattern allowing it to interact with the
			other plugins in the system. For example, the application plugin can
			contain the database object, but the CRM plugin can import that
			database to reuse it's database connection handle, without knowing
			the exact details of the database connection itself, it's insulated
			from them</li>
		<li>A URL router which allows bi-directional mapping between a name
			and a url, allowing you to write a website using placeholder names
			for the urls you actually want to use, the router will put the
			correct URL according to it's configuration. Meaning your code is
			cleaner and if the SEO/SEM nature of a page changes, altering the
			configuration will automatically update all uses of that url in the
			website so you don't have to clean and update everything by hand</li>
		<li>A new database router was also created, a bit step up over the
			older XML router. It means you can define routes inside the database
			and it'll create the final router configuration using that. This
			means a user interface can be built to manage the entire sitemap of
			the website, altering it's urls, the parameters passed to each route,
			it's properties etc, all without altering a single line of
			programming code, putting more power in the hands of normal people.</li>
		<li>Webservices are a major feature, you can define a different kind
			of route, called a service, give a URL and a set of "handlers" each
			one will be executed in sequence and all the results collated and
			returned to the caller either using JSON or SESSION to store the
			data. It means you can cleanly separate processing your data from
			rendering your website.</li>
		<li>This feature allows the construction of API's in a simple and
			logical fashion, completely separated from your website you can
			construct an API to read and write data to the database and construct
			a website solely as the user interface that attaches to that API.</li>
		<li>The router even allows you to export and this can be used in your
			website to import to your local router, meaning you can reference API
			methods using their placeholder names, instead of direct URLs,
			meaning again, more automation. If somebody upgrades the API with new
			urls, your website automatically picks up those new urls, or
			configurations, as long as nothing major changes, such as the data
			format that is returned from the API, your website code should
			upgrade automatically with no issue. You can even version API's and
			import a specific version</li>
	</ul>

	<p> <b>Obviously, as just an overview</b> of the functionality present in Amslib
		This document will not tell you everything.  The purpose is just to give a history
		Of the how and the why of what went on behind the scenes and the thinking
		that went into creating the library.  I know that some programmers will disagree
		with somethings that I've done, maybe you agree, if so, thats great!<br/>
		<br/>
		I hope that Amslib can provide you with the tools that you need to write a
		solid, easy custom website in the near future
	</p>
</section>
