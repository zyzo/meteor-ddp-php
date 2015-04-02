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
}
