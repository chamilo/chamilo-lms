kampfer.addDependency('ajax.js', ['ajax'], []);
kampfer.addDependency('base.js', [], []);
kampfer.addDependency('class.js', ['Class'], []);
kampfer.addDependency('data.js', ['data'], ['browser.support']);
kampfer.addDependency('events.js', ['events','events.Event','events.Listener'], ['data']);
kampfer.addDependency('eventtarget.js', ['events.EventTarget'], ['events','Class']);
kampfer.addDependency('support.js', ['browser.support'], []);
