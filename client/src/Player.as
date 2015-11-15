package {
	public class Player {

		public var uid:String;
		public var name:String;
		public var isHost:Boolean;

		public function Player(uid:String, name:String, isHost:Boolean) {
			this.uid = uid;
			this.name = name;
			this.isHost = isHost;
		}
	}
}
