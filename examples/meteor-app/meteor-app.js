Foo = new Meteor.Collection('Foo');
if (Meteor.isClient) {
    // nothing to do with the client
}

if (Meteor.isServer) {
  var i = 0;
  Meteor.methods({
      foo : function (arg) {
          console.log('foo got hit !');
          check(arg, Number);
          if (arg == 1) { return 42; }
          return "You suck";
      },
      foo2 : function() {
          console.log('foo2 got hit ' + i + ' times !');
          return i++;
      }
  });

  Meteor.publish('Foo', function() {
      return Foo.find();
  });

   if (Foo.find().count() == 0) {
      var data = [ {value : 'toto'}, {value: 'titi'}];
        for (i = 0; i < data.length; i++) {
            Foo.insert(data[i]);
      }
   }
}
