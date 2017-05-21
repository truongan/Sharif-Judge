#define main themainmainfunction
#include <iostream>
#include <string>
#include <algorithm>
#include <cctype>
 
 using namespace std;
  
  int main(){
      string a;
       
           while(getline(cin, a)){
	           for(int i = 0; i < a.length(); i++){
		               a[i] = tolower(a[i]);
			               }
				        
					        if (a.find("problem") == -1) cout << "no" ;
						        else cout << "yes";
							        cout << endl;
								    }
								    }
